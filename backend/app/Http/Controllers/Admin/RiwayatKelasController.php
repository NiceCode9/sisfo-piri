<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Koreksi riwayat kelas.
 *
 * `RiwayatKelas` adalah sumber keanggotaan rombel sekaligus sumber
 * rekonstruksi histori. Baris yang salah merusak rekap absensi, nilai tugas,
 * dan nilai ujian sekaligus, dan sebelum halaman ini ada satu-satunya cara
 * memperbaikinya adalah mengetik SQL di server.
 *
 * Karena inilah satu-satunya tempat di sistem yang dapat menghapus data
 * histori. `destroy()` karena itu memeriksa dampaknya dan tidak hanya
 * meminta konfirmasi.
 */
class RiwayatKelasController extends Controller implements HasMiddleware
{
    /**
     * Filter daftar. Semuanya kecuali `semua` menampilkan HANYA siswa yang
     * bermasalah, karena gunanya halaman ini adalah menemukan yang salah —
     * bukan memeriksa semua orang satu per satu.
     */
    public const FILTER = ['semua', 'tanpa-aktif', 'drift', 'ganda-aktif'];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:riwayat-kelas.manage'),
        ];
    }

    public function index(Request $request): View
    {
        $filter = $request->query('filter');
        $filter = in_array($filter, self::FILTER, true) ? $filter : 'semua';

        // Anomali dihitung lewat subquery, bukan lewat loop atas hasil paginate.
        // Nama dan kelas dihitung hanya untuk siswa yang lolos filter, supaya
        // daftar 90 siswa tidak memaksa tiga subquery per baris.
        $tanpaAktif = $this->tanpaBarisAktif();
        $drift = $this->pointerTidakSinkron();
        $ganda = $this->duaBarisAktif();

        // Gabungan ketiga kondisi, sebagai subquery agar bisa dipakai ulang di
        // badge tanpa query tambahan per siswa.
        $bermasalah = $this->bermasalah($tanpaAktif, $drift, $ganda);

        $siswas = Siswa::with(['user', 'kelas', 'tahunAjaran'])
            ->withCount('riwayatKelas')
            ->when($request->query('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$s}%"))
                    // NIS/NISN ikut dicari karena itu yang biasanya dipakai
                    // saat venus mencari siswa yang salah catat.
                    ->orWhere('nis', 'like', "%{$s}%")
                    ->orWhere('nisn', 'like', "%{$s}%");
            }))
            ->when($filter === 'tanpa-aktif', fn ($q) => $q->whereIn('id', $tanpaAktif))
            ->when($filter === 'drift', fn ($q) => $q->whereIn('id', $drift))
            ->when($filter === 'ganda-aktif', fn ($q) => $q->whereIn('id', $ganda))
            ->orderBy('nis')
            ->paginate(20)
            ->withQueryString();

        return view('admin.riwayat-kelas.index', [
            'siswas' => $siswas,
            'filter' => $filter,
            'filterTersedia' => self::FILTER,
            'tanpaAktif' => $tanpaAktif,
            'drift' => $drift,
            'ganda' => $ganda,
            'jumlahTanpaAktif' => count($tanpaAktif),
            'jumlahDrift' => count($drift),
            'jumlahGanda' => count($ganda),
        ]);
    }

    /**
     * Id siswa yang tidak punya satu pun baris `aktif`.
     *
     * Siswa seperti ini tidak muncul di rekap absensi, nilai tugas, maupun
     * nilai ujian — bukan karena datanya salah, tapi karena `Rombel::anggotaIds()`
     * membaca riwayat dan tidak menemukan barisnya.
     *
     * @return list<int>
     */
    protected function tanpaBarisAktif(): array
    {
        return Siswa::query()
            ->whereDoesntHave('riwayatKelas', fn ($q) => $q->where('status', 'aktif'))
            ->pluck('id')
            ->all();
    }

    /**
     * Id siswa yang `siswas.kelas_id` tidak cocok dengan baris riwayat `aktif`
     * **pada tahun ajaran yang sama**.
     *
     * Korelasi tahun itu wajib. Tanpa itu, setiap siswa yang pernah naik
     * kelas ikut terbaca: baris 7A/tahun-1 `aktif` dibandingkan dengan pointer
     * 8A/tahun-2, dan `whereColumn` yang tidak memfilter tahun menganggapnya
     * berbeda. Semua siswa pasca kenaikan akan ditandai "Pointer tidak
     * sinkron" padahal tidak ada yang salah.
     *
     * Status `aktif` pada baris tahun yang sudah lewat itu benar, bukan sisa:
     * `pindah` hanya dipakai untuk perpindahan kelas DI DALAM satu tahun
     * ajaran, dan `Rombel::anggotaIds()` memfilter tahun sebelum menghitung
     * keanggotaan, jadi baris lama tidak mencemari rombel tahun baru.
     *
     * @return list<int>
     */
    protected function pointerTidakSinkron(): array
    {
        return Siswa::query()
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('riwayat_kelas')
                    ->whereColumn('riwayat_kelas.siswa_id', 'siswas.id')
                    ->where('riwayat_kelas.status', 'aktif')
                    // Tahun harus sama dengan pointer siswa. Kalau pointer-nya
                    // NULL, `whereColumn` tidak akan match sama sekali — tahun
                    // yang tidak diketahui memang tidak bisa dinilai.
                    ->whereColumn('riwayat_kelas.tahun_ajaran_id', 'siswas.tahun_ajaran_id')
                    ->whereColumn('riwayat_kelas.kelas_id', '!=', 'siswas.kelas_id');
            })
            ->pluck('id')
            ->all();
    }

    /**
     * Id siswa yang punya lebih dari satu baris `aktif` dalam satu tahun ajaran.
     *
     * @return list<int>
     */
    protected function duaBarisAktif(): array
    {
        return DB::table('riwayat_kelas')
            ->where('status', 'aktif')
            ->groupBy('siswa_id', 'tahun_ajaran_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('siswa_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Gabungan ketiga daftar anomali, untuk badge ringkas di view.
     *
     * @param  list<int>  $tanpaAktif
     * @param  list<int>  $drift
     * @param  list<int>  $ganda
     * @return list<int>
     */
    protected function bermasalah(array $tanpaAktif, array $drift, array $ganda): array
    {
        return array_values(array_unique([...$tanpaAktif, ...$drift, ...$ganda]));
    }

    /**
     * Riwayat satu siswa.
     */
    public function show(Siswa $siswa): View
    {
        $riwayats = $siswa->riwayatKelas()
            ->with(['kelas', 'tahunAjaran'])
            ->orderByDesc('tahun_ajaran_id')
            ->orderByDesc('id')
            ->get();

        $tahunAjarans = TahunAjaran::orderByDesc('tanggal_mulai')->get();
        $tahunAktif = TahunAjaran::aktif()->first();

        return view('admin.riwayat-kelas.show', [
            'siswa' => $siswa->load(['user', 'kelas', 'tahunAjaran']),
            'riwayats' => $riwayats,
            'kelases' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'tahunAjarans' => $tahunAjarans,
            // Untuk menandai baris `aktif` yang berasal dari tahun ajaran yang
            // sudah lewat, supaya tidak terbaca sebagai kelas siswa sekarang.
            'tahunAktif' => $tahunAktif,
        ]);
    }

    /**
     * Tambah baris riwayat untuk siswa yang belum punya.
     *
     * Status bebas diisi, tapi penyelarasan pointer hanya terjadi kalau
     * statusnya `aktif`. Menambah baris historis (mis. mengulang tahun lalu)
     * tidak boleh memindahkan siswa sekarang.
     */
    public function store(Request $request, Siswa $siswa): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
            'status' => ['required', 'in:aktif,lulus,pindah,dropout,mengulang'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        // Constraint unik (siswa, kelas, tahun) yang sudah dipasang akan
        // menolak bentrok. Dicek di sini supaya pesannya terbaca.
        if ($this->sudahPunya($siswa->id, $validated['kelas_id'], $validated['tahun_ajaran_id'])) {
            return back()->withErrors([
                'kelas_id' => 'Siswa ini sudah punya baris riwayat untuk kelas dan tahun ajaran tersebut.',
            ])->withInput();
        }

        DB::transaction(function () use ($siswa, $validated) {
            if ($validated['status'] === 'aktif') {
                // Melepas baris aktif lain di tahun yang sama, supaya siswa
                // tidak jadi anggota dua rombel sekaligus. Sama seperti
                // `update()`, dan sama seperti yang dilakukan `SiswaController`
                // saat kelas diganti.
                RiwayatKelas::where('siswa_id', $siswa->id)
                    ->where('status', 'aktif')
                    ->where('tahun_ajaran_id', $validated['tahun_ajaran_id'])
                    ->update(['status' => 'pindah']);
            }

            RiwayatKelas::create($validated + ['siswa_id' => $siswa->id]);

            // Tanpa ini, baris `aktif` yang baru dibuat langsung menjadi
            // anomali "pointer tidak sinkron" — formnya sendiri yang
            // memunculkan masalah yang seharusnya ia perbaiki.
            if ($validated['status'] === 'aktif') {
                $siswa->update([
                    'kelas_id' => $validated['kelas_id'],
                    'tahun_ajaran_id' => $validated['tahun_ajaran_id'],
                ]);
            }
        });

        return redirect()
            ->route('admin.riwayat-kelas.show', $siswa)
            ->with('success', 'Baris riwayat ditambahkan.');
    }

    /**
     * Apakah siswa sudah punya baris untuk pasangan kelas + tahun ini.
     *
     * Satu tempat untuk `store()` dan `update()`. Kalau aturan ini ditulis dua
     * kali, keduanya akan berbeda setelah salah satu diubah — dan yang salah
     * akan lolos ke database sebagai `QueryException`, bukan pesan yang bisa
     * dibaca.
     */
    protected function sudahPunya(
        int $siswaId,
        int $kelasId,
        int $tahunAjaranId,
        ?int $abaikanId = null
    ): bool {
        return RiwayatKelas::where('siswa_id', $siswaId)
            ->where('kelas_id', $kelasId)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->when($abaikanId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->exists();
    }

    /**
     * Koreksi baris yang sudah ada.
     */
    public function update(Request $request, RiwayatKelas $riwayatKelas): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
            'status' => ['required', 'in:aktif,lulus,pindah,dropout,mengulang'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        // Constraint unik (siswa, kelas, tahun) yang baru dipasang akan
        // menolak bentrok. Dicek di sini supaya pesannya bisa dibaca alih-alih
        // menjadi QueryException.
        if ($this->sudahPunya($riwayatKelas->siswa_id, $validated['kelas_id'], $validated['tahun_ajaran_id'], $riwayatKelas->id)) {
            return back()->withErrors([
                'kelas_id' => 'Siswa ini sudah punya baris riwayat untuk kelas dan tahun ajaran tersebut.',
            ]);
        }

        // Satu siswa hanya boleh punya satu baris `aktif` per tahun ajaran.
        // Mengubah baris ini menjadi `aktif` berarti melepas baris aktif lain
        // di tahun yang sama, supaya ia tidak jadi anggota dua rombel sekaligus.
        if ($validated['status'] === 'aktif') {
            RiwayatKelas::where('siswa_id', $riwayatKelas->siswa_id)
                ->where('id', '!=', $riwayatKelas->id)
                ->where('status', 'aktif')
                ->where('tahun_ajaran_id', $validated['tahun_ajaran_id'])
                ->update(['status' => 'pindah']);
        }

        $riwayatKelas->update($validated);

        return back()->with('success', 'Riwayat kelas diperbarui.');
    }

    public function destroy(Request $request, RiwayatKelas $riwayatKelas): RedirectResponse
    {
        // Menghapus baris riwayat berarti menarik siswa ini dari rekap kelas
        // tersebut, karena keanggotaan rombel diturunkan dari tabel ini.
        // Kehadiran, tugas, dan nilai ujian yang sudah tercatat tidak akan
        // lagi muncul di rekap, meski baris aslinya masih ada di tabelnya.
        $dampak = $this->ringkasanDampak($riwayatKelas);

        if (! $request->boolean('sadar')) {
            return back()->with(
                'error',
                'Baris riwayat tidak dihapus. Tandai kotak konfirmasi terlebih dahulu. '.$dampak
            );
        }

        $riwayatKelas->delete();

        return back()->with('success', 'Baris riwayat dihapus.'.$dampak);
    }

    /**
     * Ringkasan data yang akan hilang dari rekap, untuk pesan sebelum hapus.
     */
    protected function ringkasanDampak(RiwayatKelas $riwayat): string
    {
        $rombel = Rombel::where('kelas_id', $riwayat->kelas_id)
            ->where('tahun_ajaran_id', $riwayat->tahun_ajaran_id)
            ->first();

        if (! $rombel) {
            return '';
        }

        $punya = array_filter([
            $rombel->absensis()->where('siswa_id', $riwayat->siswa_id)->exists() ? 'kehadiran' : null,
            Tugas::where('rombel_id', $rombel->id)
                ->whereHas('pengumpulans', fn ($q) => $q->where('siswa_id', $riwayat->siswa_id))
                ->exists() ? 'nilai tugas' : null,
        ]);

        if ($punya === []) {
            return '';
        }

        return ' Rekap '.implode(' dan ', $punya).' siswa ini untuk kelas tersebut langsung tidak akan muncul lagi.';
    }
}
