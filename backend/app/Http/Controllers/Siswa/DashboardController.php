<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Pengumuman;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Services\RiwayatSiswa;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Dashboard siswa: ringkasan + pintasan.
 *
 * Sengaja tidak memuat ulang isi halaman Riwayat. `siswa.riwayat` sudah
 * menampilkan rekap lengkap per tahun ajaran lewat partial
 * `components.partials.riwayat-terpadu`, dan `RiwayatSiswa::ringkas()` sudah
 * dirancang sebagai kepala halaman. Menyalin tabel yang sama ke dua tempat
 * hanya menambah query dan membuat dua halaman bisa berbeda Answers.
 *
 * Urutan hitung penting: `RiwayatSiswa::untukSiswa()` jalan sekali di awal,
 * lalu `ringkas()` dan pembacaan `$baris` cuma PHP. Menjalankan
 * `untukSiswa()` dua kali akan menggandakan 7+9N query tanpa guna.
 */
class DashboardController extends Controller
{
    /**
     * Berapa tugas yang mau ditampilkan di daftar "menunggu dikerjakan".
     */
    private const BATAS_TUGAS = 5;

    public function __invoke(): View
    {
        $user = auth()->user();
        $siswa = $user->siswa()->with('kelas')->first();

        $baris = $siswa ? RiwayatSiswa::untukSiswa($siswa) : [];
        $ringkas = RiwayatSiswa::ringkas($baris);

        // `Rombel::untukSiswa()` sudah_query. Dipanggil sekali di sini lalu
        // diteruskan ke setiap helper; memanggilnya di dalam masing-masing
        // helper akan mengulang query yang sama beberapa kali.
        $rombelIds = $siswa ? Rombel::untukSiswa($siswa) : [];

        return view('siswa.dashboard', [
            'siswa' => $siswa,
            'ringkas' => $ringkas,
            'tugasMenunggu' => $this->tugasMenunggu($siswa, $rombelIds),
            'deadlineTerdekat' => $this->deadlineTerdekat($rombelIds),
            'ujianTersedia' => $this->ujianTersedia($siswa, $rombelIds),
            'nilaiCbt' => $this->nilaiCbtTerakhir($baris),
            'pengumuman' => $this->pengumuman($siswa),
        ]);
    }

    /**
     * Tugas aktif yang belum dikumpulkan siswa.
     *
     * Dipakai `whereDoesntHave` supaya satu baris per tugas, bukan join yang
     * bisa menggandakan tugas bila siswa punya lebih dari satu pengumpulan.
     *
     * @param  array<int, int>  $rombelIds
     * @return Collection<int, Tugas>
     */
    private function tugasMenunggu(?Siswa $siswa, array $rombelIds): Collection
    {
        if ($siswa === null || $rombelIds === []) {
            return collect();
        }

        return Tugas::with(['rombel.kelas', 'mataPelajaran'])
            ->whereIn('rombel_id', $rombelIds)
            ->where('is_aktif', true)
            ->whereDoesntHave('pengumpulans', fn ($q) => $q->where('siswa_id', $siswa->id))
            ->orderByRaw('deadline is null')
            ->orderBy('deadline')
            ->limit(self::BATAS_TUGAS)
            ->get();
    }

    /**
     * Tugas yang belum lewat tenggat, untuk tenggat terdekat.
     *
     * @param  array<int, int>  $rombelIds
     * @return Collection<int, Tugas>
     */
    private function deadlineTerdekat(array $rombelIds): Collection
    {
        if ($rombelIds === []) {
            return collect();
        }

        return Tugas::with(['rombel.kelas'])
            ->whereIn('rombel_id', $rombelIds)
            ->where('is_aktif', true)
            ->whereNotNull('deadline')
            ->where('deadline', '>=', now())
            ->orderBy('deadline')
            ->limit(self::BATAS_TUGAS)
            ->get();
    }

    /**
     * Ujian CBT yang sudah dipublikasikan untuk kelas siswa, yang belum pernah
     * ia mulai.
     *
     * Syaratnya sengaja meniru gate `Api\Cbt\ExamSessionController::join()`.
     * Kalau lebih longgar dari situ, dashboard akan menjanjikan ujian yang
     * ditolak begitu siswa benar-benar memasukkan token.
     *
     * Catatan: kartu ini hanya memberi tahu, bukan memberi akses. Ujian tetap
     * lewat token, dan token tidak pernah ditampilkan di dashboard.
     *
     * @param  array<int, int>  $rombelIds
     * @return Collection<int, Exam>
     */
    private function ujianTersedia(?Siswa $siswa, array $rombelIds): Collection
    {
        if ($siswa === null || $rombelIds === [] || $siswa->user_id === null) {
            return collect();
        }

        return Exam::with(['mataPelajaran', 'rombel.kelas'])
            ->whereIn('rombel_id', $rombelIds)
            ->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('available_from')->orWhere('available_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('available_until')->orWhere('available_until', '>=', now()))
            ->whereDoesntHave('sessions', fn ($q) => $q->where('user_id', $siswa->user_id))
            ->orderBy('available_until')
            ->limit(self::BATAS_TUGAS)
            ->get();
    }

    /**
     * Nilai CBT terakhir dari riwayat terpadu, supaya angka di sini sama
     * dengan yang tampil di halaman Riwayat.
     *
     * Remedial ditangani oleh `rekapCbt()` yang sudah memilih skor terbaik
     * dalam satu rantai; dashboard tidak menyalin aturan itu sendiri.
     *
     * @param  array<int, array<string, mixed>>  $baris
     * @return array{ujian: string, mapel: ?string, skor: float, persen: int}|null
     */
    private function nilaiCbtTerakhir(array $baris): ?array
    {
        foreach ($baris as $rombel) {
            foreach ($rombel['cbt'] ?? [] as $hasil) {
                if (($hasil['skor'] ?? null) !== null) {
                    return [
                        'ujian' => $hasil['nama'],
                        'mapel' => $hasil['mapel'],
                        'skor' => (float) $hasil['skor'],
                        'persen' => (int) round($hasil['skor']),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Pengumuman aktif untuk tahun ajaran siswa.
     *
     * `pengumuman` tidak punya scoping per rombel atau per tingkat — satu
     * pengumuman berlaku untuk semua orang — jadi satu filter tahun ajaran
     * adalah batas yang tersedia, bukan pilihan.
     */
    private function pengumuman(?Siswa $siswa): Collection
    {
        return Pengumuman::where('status_aktif', true)
            ->when(
                $siswa?->tahun_ajaran_id,
                fn ($q, $tahunId) => $q->where('tahun_ajaran_id', $tahunId)
            )
            ->orderByDesc('tanggal_pengumuman')
            ->limit(3)
            ->get();
    }
}
