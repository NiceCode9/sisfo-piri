<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Exam;
use App\Models\Guru;
use App\Models\Materi;
use App\Models\Pengampu;
use App\Models\PengumpulanTugas;
use App\Models\Rombel;
use App\Models\Tugas;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Dashboard guru.
 *
 * Seluruh isi halaman ini dibatasi ke rombel yang benar-benar milik guru
 * melalui {@see Rombel::terjangkauUser()}. Scope itu wajib fail-closed: guru
 * tanpa baris `gurus` maupun tanpa penugasan harus melihat NOL, bukan seluruh
 * sekolah. Ini bukan pilihan —— kalau melonggar di sini, dashboard ini
 * langsung jadi halaman kebocoran data antar guru yang paling mudah dibaca.
 *
 * Rombel yang ditampilkan adalah gabungan "saya wali" dan "saya pengampu mapel
 * di sana", persis seperti cakupan modul lain.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $hariIni = Carbon::today();

        // `User` tidak punya relasi ke `gurus`; proyek memakai
        // `Guru::where('user_id', ...)` di mana-mana. Diambil sekali di sini
        // supaya blok di bawah tidak mengulang query yang sama.
        $guru = Guru::where('user_id', $user->id)->first();

        $rombels = $this->rombelsTerjangkau($user);
        $mapelIds = $this->mapelDiampu($guru, $rombels);

        return view('guru.dashboard', [
            'hariIni' => $hariIni,
            'guru' => $guru,
            'ringkasan' => $this->ringkasan($rombels, $guru),
            'absensiHariIni' => $this->absensiHariIni($rombels, $hariIni),
            'tugasMenungguPenilaian' => $this->tugasMenungguPenilaian($rombels, $mapelIds),
            'deadlineTerdekat' => $this->deadlineTerdekat($rombels, $mapelIds),
            'ujianSaya' => $this->ujianSaya($rombels, $mapelIds, $hariIni),
        ]);
    }

    /**
     * Mata pelajaran yang diampu guru, tanpa dibatasi rombel.
     *
     * Tanpa filter rombel karena itu yang dipakai `ExamPolicy::view()`: guru
     * yang mengampu Matematika di 8B tetap berhak atas ujian Matematika 7A
     * selama ia wali 7A.
     *
     * @param  Collection<int, Rombel>  $rombels
     * @return Collection<int, int>
     */
    private function mapelDiampu(?Guru $guru, Collection $rombels): Collection
    {
        if ($guru === null) {
            return collect();
        }

        return Pengampu::where('guru_id', $guru->id)->pluck('mata_pelajaran_id')->unique();
    }

    /**
     * Rombel milik guru. Satu definisi, dipakai keempat blok halaman.
     *
     * @return Collection<int, Rombel>
     */
    private function rombelsTerjangkau($user): Collection
    {
        return Rombel::with(['kelas', 'tahunAjaran', 'waliGuru'])
            ->terjangkauUser($user)
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->get();
    }

    /**
     * @param  Collection<int, Rombel>  $rombels
     * @return array{kelas: int, siswa: int, wali: int, tugas: int, materi: int}
     */
    /**
     * @param  Collection<int, Rombel>  $rombels
     * @return array{kelas: int, siswa: int, wali: int, tugas: int, materi: int}
     */
    private function ringkasan(Collection $rombels, ?Guru $guru): array
    {
        $ids = $rombels->pluck('id');

        if ($ids->isEmpty()) {
            return ['kelas' => 0, 'siswa' => 0, 'wali' => 0, 'tugas' => 0, 'materi' => 0];
        }

        // `anggotaIdsUntukRekap()` sudah memilih anggota aktif untuk tahun
        // berjalan dan seluruh histori untuk tahun lama. Kelembaliannya array
        // biasa, bukan Collection.
        $siswa = 0;

        foreach ($rombels as $rombel) {
            $siswa += count($rombel->anggotaIdsUntukRekap());
        }

        return [
            'kelas' => $rombels->count(),
            'siswa' => $siswa,
            // "Wali" berarti rombel yang menjadi wali kelasnya sendiri, bukan
            // sekadar rombel yang punya wali.
            'wali' => $guru === null ? 0 : $rombels->where('wali_guru_id', $guru->id)->count(),
            'tugas' => Tugas::whereIn('rombel_id', $ids)->where('is_aktif', true)->count(),
            'materi' => Materi::whereIn('rombel_id', $ids)->where('is_aktif', true)->count(),
        ];
    }

    /**
     * Status absensi hari ini untuk tiap rombel milik guru.
     *
     * Satu query untuk semua rombel, lalu dikelompokkan di PHP. Mengulang
     * query per rombel untuk 10 kelas berarti 10 perjalanan bolak-balik ke
     * database hanya untuk menghitung angka yang sama.
     *
     * @param  Collection<int, Rombel>  $rombels
     * @return Collection<int, array{rombel: Rombel, tercatat: int, total: int, persen: int}>
     */
    private function absensiHariIni(Collection $rombels, Carbon $hari): Collection
    {
        if ($rombels->isEmpty()) {
            return collect();
        }

        $perRombel = Absensi::whereIn('rombel_id', $rombels->pluck('id'))
            ->whereDate('tanggal', $hari->toDateString())
            ->selectRaw('rombel_id, COUNT(*) as total, SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as hadir', ['hadir', 'terlambat'])
            ->groupBy('rombel_id')
            ->get()
            ->keyBy('rombel_id');

        $totalSiswa = [];

        foreach ($rombels as $rombel) {
            $totalSiswa[$rombel->id] = count($rombel->anggotaIdsUntukRekap());
        }

        return $rombels->map(function (Rombel $rombel) use ($perRombel, $totalSiswa) {
            $baris = $perRombel->get($rombel->id);
            $total = $totalSiswa[$rombel->id];
            $hadir = (int) ($baris->hadir ?? 0);

            return [
                'rombel' => $rombel,
                'tercatat' => (int) ($baris->total ?? 0),
                'total' => $total,
                // Tanpa anggota berarti belum ada yang absen sama sekali, bukan
                // 0% — menampilkan "0% kehadiran" untuk kelas yang belum diisi
                // akan terbaca sebagai kegagalan guru.
                'persen' => $total === 0 ? null : (int) round($hadir / $total * 100),
                'sudah' => $total > 0 && $hadir >= $total,
            ];
        });
    }

    /**
     * Pengumpulan tugas milik kelas yang diajar guru yang belum dinilai.
     *
     * Di-scope lewat rombel yang sudah terjangkau, bukan lewat `guru_id`:
     * guru sering mengampu mapel di rombel yang bukan dia wali, dan pengumpulan
     * siswa di sana tetap menjadi tanggung jawabnya.
     *
     * @param  Collection<int, Rombel>  $rombels
     * @param  Collection<int, int>  $mapelIds
     * @return Collection<int, PengumpulanTugas>
     */
    private function tugasMenungguPenilaian(Collection $rombels, Collection $mapelIds): Collection
    {
        if ($rombels->isEmpty() || $mapelIds->isEmpty()) {
            return collect();
        }

        return PengumpulanTugas::with(['tugas.rombel.kelas', 'tugas.mataPelajaran', 'siswa.user'])
            ->whereNull('nilai')
            ->whereHas('tugas', fn ($q) => $q->where('is_aktif', true)
                ->whereIn('rombel_id', $rombels->pluck('id'))
                ->whereIn('mata_pelajaran_id', $mapelIds))
            ->latest()
            ->limit(8)
            ->get();
    }

    /**
     * Tugas aktif yang deadline-nya paling dekat.
     *
     * @param  Collection<int, Rombel>  $rombels
     * @param  Collection<int, int>  $mapelIds
     * @return Collection<int, Tugas>
     */
    private function deadlineTerdekat(Collection $rombels, Collection $mapelIds): Collection
    {
        if ($rombels->isEmpty() || $mapelIds->isEmpty()) {
            return collect();
        }

        return Tugas::with(['rombel.kelas', 'mataPelajaran'])
            ->where('is_aktif', true)
            ->whereIn('rombel_id', $rombels->pluck('id'))
            ->whereIn('mata_pelajaran_id', $mapelIds)
            ->whereNotNull('deadline')
            ->where('deadline', '>=', now())
            ->orderBy('deadline')
            ->limit(5)
            ->get();
    }

    /**
     * Ujian CBT yang boleh dilihat guru: berstatus published, di rombel yang ia
     * wali atau mengampu mapelnya.
     *
     * Syaratnya sengaja dua kondisi terpisah, bukan satu EXISTS berpasangan
     * rombel+mapel: `ExamPolicy::view()` memeriksa `Pengampu` tanpa filter
     * rombel, jadi guru yang mengampu Matematika di 8B tetap berhak atas
     * ujian Matematika 7A selama ia wali 7A. Persempit kelewat akan
     * menghilangkan haknya itu.
     *
     * @param  Collection<int, Rombel>  $rombels
     * @param  Collection<int, int>  $mapelIds
     * @return Collection<int, Exam>
     */
    private function ujianSaya(Collection $rombels, Collection $mapelIds, Carbon $hari): Collection
    {
        if ($rombels->isEmpty() || $mapelIds->isEmpty()) {
            return collect();
        }

        return Exam::with(['rombel.kelas', 'mataPelajaran'])
            ->withCount('tokens')
            ->where('status', 'published')
            ->whereIn('rombel_id', $rombels->pluck('id'))
            ->whereIn('mata_pelajaran_id', $mapelIds)
            ->where(fn ($q) => $q->whereNull('available_until')->orWhere('available_until', '>=', $hari))
            ->orderBy('available_until')
            ->limit(5)
            ->get();
    }
}
