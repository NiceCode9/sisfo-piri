<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Admin\AbsensiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ortu\AnakAbsensiRequest;
use App\Models\Absensi;
use App\Models\ExamSession;
use App\Models\PengumpulanTugas;
use App\Models\Pengumuman;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\WaliMurid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Batas baris per daftar. Daftar memotong di sini, query tetap mengambil
     * seluruh baris supaya jumlahnya tidak bergantung pada ini.
     */
    private const BATAS = 5;

    /**
     * Daftar anak + status kehadiran hari ini + wali + persen bulan, ditambah
     * ringkasan tugas, nilai, dan pengumuman.
     *
     * Semua tambahan diambil satu kali untuk seluruh anak. `OrtuViewTest`
     * mengukur pertumbuhan jumlah query di tiga titik (1 anak, 2 anak, 3 anak)
     * dan menuntut selisihnya nol, jadi satu query di dalam loop anak akan
     * langsung menggagalkannya. Jangan tambahkan loop yang melakukan query di
     * sini tanpa mengubah test itu juga — atau lebih baik, jangan ubah.
     */
    public function index(): View
    {
        $anak = WaliMurid::with(['siswa.user', 'siswa.kelas', 'siswa.tahunAjaran'])
            ->where('user_id', auth()->id())
            ->get();

        $hari = now()->toDateString();
        $siswaIds = $anak->pluck('siswa_id')->filter();

        $statusHariIni = Absensi::whereIn('siswa_id', $siswaIds)
            ->where('tanggal', $hari)
            ->orderBy('id')
            ->get()
            ->keyBy('siswa_id');

        $bulan = now()->format('Y-m');
        $waliPerSiswa = [];
        $persenPerSiswa = [];

        // Satu akun orang tua bisa punya beberapa anak — nomor WA yang sama
        // dipakai bersama antar saudara. Versi lama melakukan dua query per
        // anak di dalam loop (cari rombel, lalu agregat bulanan), sehingga
        // total query ikut bertambah mengikuti jumlah anak. Berikutnya kedua
        // sumber diambil sekali untuk seluruh anak lalu dipetakan.
        $pasangan = $anak->pluck('siswa')
            ->filter()
            ->map(fn (Siswa $siswa) => [$siswa->kelas_id, $siswa->tahun_ajaran_id])
            ->unique(fn (array $pair) => $pair[0].':'.$pair[1]);

        // Tanpa pasangan, `where(function () {})` berarti tanpa syarat sama
        // sekali dan rombel yang termuat jadi seluruh sekolah. Karena itu
        // daftar rombel sengaja dikosongkan secara eksplisit.
        $rombels = $pasangan->isEmpty()
            ? collect()
            : Rombel::with('waliGuru')
                ->where(function (Builder $q) use ($pasangan) {
                    foreach ($pasangan as [$kelasId, $tahunAjaranId]) {
                        $q->orWhere(fn ($qq) => $qq->where('kelas_id', $kelasId)->where('tahun_ajaran_id', $tahunAjaranId));
                    }
                })
                ->get()
                ->keyBy(fn (Rombel $rombel) => $rombel->kelas_id.':'.$rombel->tahun_ajaran_id);

        $porsiPerSiswa = Absensi::whereIn('siswa_id', $siswaIds)
            ->where('tanggal', 'like', $bulan.'%')
            ->selectRaw('siswa_id, status, COUNT(DISTINCT tanggal) as jumlah')
            ->groupBy('siswa_id', 'status')
            ->get()
            ->groupBy('siswa_id');

        foreach ($anak as $tautan) {
            $siswa = $tautan->siswa;

            if ($siswa) {
                $rombel = $rombels->get($siswa->kelas_id.':'.$siswa->tahun_ajaran_id);
                $waliPerSiswa[$siswa->id] = $rombel?->waliGuru?->nama;

                $rekap = [];
                foreach ($porsiPerSiswa->get($siswa->id, collect()) as $baris) {
                    $rekap[$baris->status] = (int) $baris->jumlah;
                }

                $total = array_sum($rekap);
                $persenPerSiswa[$siswa->id] = $total ? round((($rekap['hadir'] ?? 0) + ($rekap['terlambat'] ?? 0)) / $total * 100) : null;
            }
        }

        // Rombel anak diambil dari peta yang sudah termuat di atas untuk lookup
        // wali, bukan query baru.
        $rombelIdsAnak = $anak->pluck('siswa')
            ->filter()
            ->map(fn (Siswa $s) => $rombels->get($s->kelas_id.':'.$s->tahun_ajaran_id)?->id)
            ->filter()
            ->unique()
            ->values();

        return view('ortu.dashboard', [
            'anak' => $anak,
            'statusHariIni' => $statusHariIni,
            'waliPerSiswa' => $waliPerSiswa,
            'persenPerSiswa' => $persenPerSiswa,
            'hari' => $hari,
            'rombelIdsAnak' => $rombelIdsAnak,
            'tugasMenunggu' => $this->tugasMenunggu($rombelIdsAnak, $siswaIds),
            'deadlineTerdekat' => $this->deadlineTerdekat($rombelIdsAnak),
            'rataNilaiTugas' => $this->rataNilaiTugas($siswaIds),
            'nilaiCbtTerakhir' => $this->nilaiCbtTerakhir($anak->pluck('siswa')->filter()->pluck('user_id')->filter()),
            'pengumuman' => $this->pengumuman($anak->pluck('siswa')->filter()->pluck('tahun_ajaran_id')->unique()),
        ]);
    }

    /**
     * Tugas yang belum dikumpulkan, dikelompokkan per anak.
     *
     * Dua query untuk semua anak: daftar tugas aktif di rombel anak, lalu
     * seluruh pengumpulan anak. Sisanya dihitung di PHP. Versi per anak dengan
     * `whereDoesntHave` akan jadi satu query per anak, dan penjaga query di
     * `OrtuViewTest` akan langsung merah.
     *
     * @param  Collection<int, int>  $rombelIds
     * @param  Collection<int, int>  $siswaIds
     * @return array<int, Collection<int, Tugas>>
     */
    private function tugasMenunggu(Collection $rombelIds, Collection $siswaIds): array
    {
        if ($rombelIds->isEmpty() || $siswaIds->isEmpty()) {
            return [];
        }

        $tugas = Tugas::with(['rombel.kelas', 'mataPelajaran'])
            ->whereIn('rombel_id', $rombelIds)
            ->where('is_aktif', true)
            ->where(fn ($q) => $q->whereNull('deadline')->orWhere('deadline', '>=', now()))
            ->orderBy('deadline')
            ->orderBy('id')
            ->get();

        // Dikelompokkan sebagai "siswa_id => [tugas_id]" supaya pengecekan
        // nanti bisa O(1) per anak, bukan O(tugas x pengumpulan).
        $dikumpulPerSiswa = PengumpulanTugas::whereIn('siswa_id', $siswaIds)
            ->get(['siswa_id', 'tugas_id'])
            ->groupBy('siswa_id')
            ->map(fn ($baris) => $baris->pluck('tugas_id')->flip());

        $hasil = [];

        foreach ($siswaIds as $siswaId) {
            $sudah = $dikumpulPerSiswa->get($siswaId, collect());

            $hasil[$siswaId] = $tugas
                ->reject(fn (Tugas $t) => $sudah->has($t->id))
                ->take(self::BATAS)
                ->values();
        }

        return $hasil;
    }

    /**
     * Tugas dengan tenggat terdekat, semua anak.
     *
     * @param  Collection<int, int>  $rombelIds
     * @return Collection<int, Tugas>
     */
    private function deadlineTerdekat(Collection $rombelIds): Collection
    {
        if ($rombelIds->isEmpty()) {
            return collect();
        }

        return Tugas::with(['rombel.kelas'])
            ->whereIn('rombel_id', $rombelIds)
            ->where('is_aktif', true)
            ->whereNotNull('deadline')
            ->where('deadline', '>=', now())
            ->orderBy('deadline')
            ->limit(self::BATAS)
            ->get();
    }

    /**
     * Rata-rata nilai tugas per anak, satu agregat.
     *
     * @param  Collection<int, int>  $siswaIds
     * @return array<int, float|null>
     */
    private function rataNilaiTugas(Collection $siswaIds): array
    {
        if ($siswaIds->isEmpty()) {
            return [];
        }

        $hasil = [];

        foreach (PengumpulanTugas::whereIn('siswa_id', $siswaIds)
            ->whereNotNull('nilai')
            ->selectRaw('siswa_id, AVG(nilai) as rata')
            ->groupBy('siswa_id')
            ->get() as $baris) {
            $hasil[(int) $baris->siswa_id] = (float) $baris->rata;
        }

        return $hasil;
    }

    /**
     * Skor sesi CBT terakhir per anak.
     *
     * BUKAN skor yang masuk rapor. Rapor memakai skor terbaik dalam satu rantai
     * remedial (lihat `RiwayatSiswa::rekapCbt()`), dan aturan itu tidak bisa
     * disalin ke satu query tanpa menyalin aturannya lagi — persis jebakan
     * yang `CbtRemedialTest` sengaja kunci. Jadi view wajib melabeli ini "Nilai
     * Terakhir" supaya tidak disalahartikan sebagai nilai rapor.
     *
     * @param  Collection<int, int>  $userIds
     * @return array<int, array{skor: float, nama: string}>
     */
    private function nilaiCbtTerakhir(Collection $userIds): array
    {
        if ($userIds->isEmpty()) {
            return [];
        }

        $hasil = [];

        $sesi = ExamSession::with('exam')
            ->whereIn('user_id', $userIds)
            ->whereNotNull('score')
            ->whereNotNull('finished_at')
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->get();

        foreach ($sesi as $s) {
            // Order di atas sudah menurun, jadi kemunculan pertama per user
            // adalah sesi terakhir.
            $hasil[(int) $s->user_id] ??= [
                'skor' => (float) $s->score,
                'nama' => $s->exam?->name ?? 'Ujian',
            ];
        }

        return $hasil;
    }

    /**
     * Pengumuman aktif untuk tahun ajaran anak.
     *
     * `pengumuman` tidak punya scoping per rombel atau per tingkat, jadi satu
     * filter tahun ajaran adalah batas yang tersedia -- bukan pilihan.
     *
     * @param  Collection<int, int>  $tahunAjaranIds
     * @return Collection<int, Pengumuman>
     */
    private function pengumuman(Collection $tahunAjaranIds): Collection
    {
        if ($tahunAjaranIds->isEmpty()) {
            return collect();
        }

        return Pengumuman::where('status_aktif', true)
            ->whereIn('tahun_ajaran_id', $tahunAjaranIds)
            ->orderByDesc('tanggal_pengumuman')
            ->limit(self::BATAS)
            ->get();
    }

    /**
     * Detail satu anak: rekap periode (bulan/ganjil/genap/tahun) + riwayat.
     */
    public function show(WaliMurid $waliMurid, AnakAbsensiRequest $request): View
    {
        abort_unless($waliMurid->user_id === auth()->id(), 403, 'Bukan anak asuh Anda.');

        $waliMurid->load(['siswa.user', 'siswa.kelas', 'siswa.tahunAjaran']);

        $periode = $request->query('periode', 'bulan');
        $acuan = $request->query('acuan', now()->format('Y-m'));
        if ($periode === 'bulan' && strlen($acuan) === 10) {
            $acuan = substr($acuan, 0, 7);
        }
        $tahunAjaranId = $request->query('tahun_ajaran_id') ?: ($waliMurid->siswa->tahun_ajaran_id ?? TahunAjaran::aktif()->first()?->id);
        $acuanTanggal = $periode === 'bulan' ? $acuan.'-01' : (strlen($acuan) === 7 ? $acuan.'-01' : $acuan);
        $rentang = AbsensiController::rentangPeriode($periode, $acuanTanggal, $tahunAjaranId ? (int) $tahunAjaranId : null);

        $riwayat = Absensi::with('rombel.kelas')
            ->where('siswa_id', $waliMurid->siswa_id)
            ->whereBetween('tanggal', [$rentang['mulai'], $rentang['selesai']])
            ->orderByDesc('tanggal')
            ->get();

        $rekap = Absensi::where('siswa_id', $waliMurid->siswa_id)
            ->whereBetween('tanggal', [$rentang['mulai'], $rentang['selesai']])
            ->selectRaw('status, COUNT(DISTINCT tanggal) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status')
            ->all();

        $total = array_sum($rekap);
        $persen = $total ? round((($rekap['hadir'] ?? 0) + ($rekap['terlambat'] ?? 0)) / $total * 100) : null;

        $rombel = $waliMurid->siswa ? Rombel::with('waliGuru')->where('kelas_id', $waliMurid->siswa->kelas_id)
            ->where('tahun_ajaran_id', $waliMurid->siswa->tahun_ajaran_id)->first() : null;

        return view('ortu.anak', [
            'tautan' => $waliMurid,
            'periode' => $periode,
            'acuan' => $acuan,
            'rentang' => $rentang,
            'riwayat' => $riwayat,
            'rekap' => $rekap,
            'persen' => $persen,
            'wali' => $rombel?->waliGuru?->nama,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAjaranId' => $tahunAjaranId,
        ]);
    }
}
