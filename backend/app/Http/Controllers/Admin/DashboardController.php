<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\CalonSiswa;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Guru;
use App\Models\Pembayaran;
use App\Models\PengumpulanTugas;
use App\Models\Rombel;
use App\Models\Siswa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Dashboard sekolah.
 *
 * Prinsip: hitung di SQL, jangan ambil baris lalu loop di PHP. Absensi satu
 * hari untuk 90 siswa sudah ratusan baris, dan versi pertama dashboard ini
 * memuat 9 tabel besar sekaligus — memuatnya ke memori hanya supaya dijumlahkan
 * di view adalah pemborosan yang tidak perlu.
 *
 * Semua angka sengaja punya padanan teks di markup. Chart cuma penghias;
 * halaman harus tetap berguna ketika JS gagal atau chart tidak termuat.
 */
class DashboardController extends Controller
{
    /**
     * Nilai enum `absensis.status`, urutan untuk tampilan.
     *
     * @var array<int, string>
     */
    private const STATUS_ABSENSI = ['hadir', 'terlambat', 'sakit', 'izin', 'alpa'];

    /**
     * Warna badge per status absensi. Satu definisi, dipakai kartu dan tabel.
     *
     * @var array<string, string>
     */
    private const WARNA_ABSENSI = [
        'hadir' => 'badge-success',
        'terlambat' => 'badge-warning',
        'sakit' => 'badge-info',
        'izin' => 'badge-info',
        'alpa' => 'badge-danger',
    ];

    /**
     * Nilai enum `calon_siswas.status_pendaftaran`.
     *
     * @var array<string, string>
     */
    private const LABEL_PPDB = [
        'menunggu' => 'Menunggu',
        'diterima' => 'Diterima',
        'ditolak' => 'Ditolak',
        'daftar_ulang' => 'Daftar Ulang',
    ];

    /**
     * Nilai enum `pembayarans.status` yang dihitung sebagai pemasukan.
     *
     * @var array<int, string>
     */
    private const PEMBAYARAN_MASUK = ['berhasil'];

    public function index(): View
    {
        $hariIni = Carbon::today();

        return view('admin.dashboard', [
            'hariIni' => $hariIni,
            'jumlahSiswa' => Siswa::where('is_aktif', true)->count(),
            'jumlahGuru' => Guru::where('is_aktif', true)->count(),
            'jumlahRombel' => Rombel::count(),
            'absensiHariIni' => $this->absensiHariIni($hariIni),
            'trenKehadiran' => $this->trenKehadiran($hariIni),
            'ppdb' => $this->rekapPpdb(),
            'trenPendaftaran' => $this->trenPendaftaran($hariIni),
            'pembayaranBulanIni' => $this->pembayaranBulanIni($hariIni),
            'antreanPenilaian' => $this->antreanPenilaian(),
            'ujianBerjalan' => $this->ujianBerjalan($hariIni),
            'sesiOngoing' => ExamSession::where('status', 'ongoing')->count(),
            'statusAbsensi' => self::STATUS_ABSENSI,
            'warnaAbsensi' => self::WARNA_ABSENSI,
            'labelPpdb' => self::LABEL_PPDB,
        ]);
    }

    /**
     * Komposisi absensi hari ini. Satu query, tanpa memuat model.
     *
     * @return array<string, int>
     */
    private function absensiHariIni(Carbon $hari): array
    {
        $hitung = Absensi::whereDate('tanggal', $hari->toDateString())
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $hasil = [];

        foreach (self::STATUS_ABSENSI as $status) {
            $hasil[$status] = (int) ($hitung[$status] ?? 0);
        }

        return $hasil;
    }

    /**
     * Persentase hadir per hari, 30 hari ke belakang.
     *
     * `terlambat` dihitung sebagai hadir — siswa yang telat tetap masuk sekolah.
     * Kalau tidak, angka kehadiran terlihat lebih buruk dari kenyataannya.
     *
     * @return array<int, array{tanggal: string, label: string, persen: int, tercatat: int}>
     */
    private function trenKehadiran(Carbon $hari): array
    {
        $mulai = $hari->copy()->subDays(29);

        // Query builder, bukan Eloquent: yang dipakai hanya baris agregat
        // sekali jalan, dan tidak ada yang butuh model di sini.
        $perTanggal = DB::table('absensis')
            ->whereBetween('tanggal', [$mulai->toDateString(), $hari->toDateString()])
            ->selectRaw('tanggal, COUNT(*) as total, SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as hadir', ['hadir', 'terlambat'])
            ->groupBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $hasil = [];

        for ($h = 0; $h < 30; $h++) {
            $tanggal = $mulai->copy()->addDays($h);
            $kunci = $tanggal->toDateString();
            $baris = $perTanggal->get($kunci);
            $total = (int) ($baris->total ?? 0);

            $hasil[] = [
                'tanggal' => $kunci,
                'label' => $tanggal->format('j M'),
                'persen' => $total === 0 ? 0 : (int) round(((int) $baris->hadir / $total) * 100),
                'tercatat' => $total,
            ];
        }

        return $hasil;
    }

    /**
     * Pendaftar per status.
     *
     * @return array<int, array{status: string, label: string, total: int}>
     */
    private function rekapPpdb(): array
    {
        $hitung = CalonSiswa::select('status_pendaftaran', DB::raw('COUNT(*) as total'))
            ->groupBy('status_pendaftaran')
            ->pluck('total', 'status_pendaftaran');

        $hasil = [];

        foreach (self::LABEL_PPDB as $status => $label) {
            $hasil[] = [
                'status' => $status,
                'label' => $label,
                'total' => (int) ($hitung[$status] ?? 0),
            ];
        }

        return $hasil;
    }

    /**
     * Pendaftar baru per bulan, 6 bulan ke belakang.
     *
     * @return array<int, array{label: string, total: int}>
     */
    private function trenPendaftaran(Carbon $hari): array
    {
        $mulai = $hari->copy()->subMonths(5)->startOfMonth();

        $baris = CalonSiswa::where('created_at', '>=', $mulai)
            ->selectRaw('created_at')
            ->get();

        // `created_at` sengaja dibaca lewat satu query kolom tunggal. Menghitung
        // per bulan di SQL butuh ekspresi yang tidak portabel antar driver
        // (MySQL MONTH() vs SQLite strftime), sedangkan tabelnya cuma berisi
        // pendaftar — bukan absensi yang ukurannya jauh lebih besar.
        $perBulan = [];

        foreach ($baris as $row) {
            $kunci = Carbon::parse($row->created_at)->format('Y-m');
            $perBulan[$kunci] = ($perBulan[$kunci] ?? 0) + 1;
        }

        $hasil = [];

        for ($i = 0; $i < 6; $i++) {
            $bulan = $mulai->copy()->addMonths($i);
            $hasil[] = [
                'label' => $bulan->translatedFormat('M Y'),
                'total' => (int) ($perBulan[$bulan->format('Y-m')] ?? 0),
            ];
        }

        return $hasil;
    }

    /**
     * Nominal penerimaan bulan berjalan.
     *
     * @return array{bulan: string, total: float, transaksi: int}
     */
    private function pembayaranBulanIni(Carbon $hari): array
    {
        $baris = Pembayaran::whereIn('status', self::PEMBAYARAN_MASUK)
            ->whereDate('tanggal_pembayaran', '>=', $hari->copy()->startOfMonth()->toDateString())
            ->whereDate('tanggal_pembayaran', '<=', $hari->copy()->endOfMonth()->toDateString())
            ->selectRaw('COALESCE(SUM(jumlah), 0) as total, COUNT(*) as transaksi')
            ->first();

        return [
            'bulan' => $hari->translatedFormat('F Y'),
            'total' => (float) ($baris->total ?? 0),
            'transaksi' => (int) ($baris->transaksi ?? 0),
        ];
    }

    /**
     * Pengumpulan yang belum dinilai — antrean kerja guru.
     *
     * @return array{total: int, contoh: Collection}
     */
    private function antreanPenilaian(): array
    {
        $total = PengumpulanTugas::whereNull('nilai')->count();

        $contoh = PengumpulanTugas::with(['tugas.rombel.kelas', 'siswa'])
            ->whereNull('nilai')
            ->latest()
            ->limit(5)
            ->get();

        return ['total' => $total, 'contoh' => $contoh];
    }

    /**
     * Ujian CBT yang sedang bisa diakses siswa pada hari ini.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Exam>
     */
    private function ujianBerjalan(Carbon $hari): Collection
    {
        $sekarang = $hari->copy()->endOfDay();

        return Exam::with(['rombel.kelas', 'mataPelajaran'])
            ->withCount('tokens')
            ->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('available_from')->orWhere('available_from', '<=', $sekarang))
            ->where(fn ($q) => $q->whereNull('available_until')->orWhere('available_until', '>=', $hari->copy()->startOfDay()))
            ->whereHas('sessions', fn ($q) => $q->where('status', 'ongoing'))
            ->orderBy('available_until')
            ->limit(5)
            ->get();
    }
}
