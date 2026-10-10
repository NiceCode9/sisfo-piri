<?php

namespace App\Services;

use App\Http\Controllers\Admin\AbsensiController;
use App\Models\Absensi;
use App\Models\ExamSession;
use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Riwayat terpadu seorang siswa: kehadiran, e-learning, dan nilai CBT,
 * dikelompokkan per rombel (kelas + tahun ajaran).
 *
 * Tiga domain itu dulu hanya bisa dibaca lewat tiga halaman terpisah —
 * rekap absensi, rekap tugas, dan hasil ujian — masing-masing dengan dropdown
 * rombel sendiri, tanpa satu pun yang saling merujuk. Sulit menjawab
 * "bagaimana perjalanan siswa ini selama dua tahun?", padahal datanya sudah
 * lengkap.
 *
 * Kunci pengelompokan adalah `rombel_id`, bukan pasangan kelas + tahun.
 * `rombels` adalah jangkar yang membawa keduanya sekaligus, dan seluruh
 * tabel historis sudah menempel padanya, sehingga rekonstruksi tidak perlu
 * menebak dari tanggal.
 *
 * Batasnya: read-only. Ini bukan rapor. Tidak ada nilai akhir, tidak ada
 * predikat, tidak ada peringkat — semua itu di luar lingkup dan perlu
 * keputusan kurikulum lebih dulu.
 */
class RiwayatSiswa
{
    /**
     * @return array<int, array{
     *     rombel: Rombel,
     *     kelas: ?string,
     *     tahun: ?string,
     *     tahunAjaranId: ?int,
     *     absensi: array{total:int, hadir:int, sakit:int, izin:int, alpa:int, terlambat:int, persen:?float},
     *     periode: array<string, array{total:int, hadir:int, alpa:int, persen:?float}>,
     *     elearning: array<int, array{mapel: ?MataPelajaran, ditugas:int, dikumpul:int, dinilai:int, belumDikumpul:int, rata:?float}>,
     *     cbt: array<int, array{nama:string, mapel:?string, skor:?float, status:string}>,
     *     cbtRata: ?float
     * }>
     */
    public static function untukSiswa(Siswa $siswa): array
    {
        $rombelIds = static::kumpulkanRombelIds($siswa);

        if ($rombelIds === []) {
            return [];
        }

        $rombels = Rombel::with(['kelas', 'tahunAjaran'])
            ->whereIn('id', $rombelIds)
            ->get()
            ->keyBy('id');

        $baris = [];

        foreach ($rombels as $id => $rombel) {
            $id = (int) $id;

            $baris[$id] = [
                'rombel' => $rombel,
                'kelas' => $rombel->kelas?->nama_kelas,
                'tahun' => $rombel->tahunAjaran?->nama_tahun_ajaran,
                'tahunAjaranId' => $rombel->tahun_ajaran_id,
                'absensi' => static::rekapAbsensi($siswa->id, $id, $rombel->tahun_ajaran_id),
                'periode' => static::rekapPeriode($siswa->id, $id, $rombel->tahun_ajaran_id),
                'elearning' => static::rekapElearning($siswa->id, $id),
                'cbt' => static::rekapCbt($siswa, $id),
                'cbtRata' => null,
            ];
        }

        foreach ($baris as $id => $r) {
            $skor = collect($r['cbt'])->pluck('skor')->filter()->values();

            $baris[$id]['cbtRata'] = $skor->isNotEmpty()
                ? round($skor->sum() / $skor->count(), 1)
                : null;
        }

        // Tahun terbaru dulu, lalu kelas yang lebih rendah.
        uasort($baris, fn ($a, $b) => [$b['tahunAjaranId'], $b['rombel']->kelas_id]
            <=> [$a['tahunAjaranId'], $a['rombel']->kelas_id]);

        return array_values($baris);
    }

    /**
     * Rombel yang perlu ditampilkan: yang punya data di salah satu domain,
     * ditambah rombel tempat siswa berada sekarang.
     *
     * @return array<int>
     */
    protected static function kumpulkanRombelIds(Siswa $siswa): array
    {
        $ids = Absensi::where('siswa_id', $siswa->id)->distinct()->pluck('rombel_id');

        $ids = $ids->merge(
            PengumpulanTugas::where('siswa_id', $siswa->id)
                ->join('tugas', 'tugas.id', '=', 'pengumpulan_tugas.tugas_id')
                ->distinct()
                ->pluck('tugas.rombel_id')
        );

        // `exam_sessions` menyimpan user_id, bukan siswa_id, jadi identitas
        // siswa dijembatan lewat siswas.user_id. Kolom siswa_id sengaja tidak
        // ditambahkan ke exam_sessions karena itu akan membuat dua sumber
        // kebenaran untuk "siapa siswa ini".
        if ($siswa->user_id) {
            $ids = $ids->merge(
                ExamSession::where('user_id', $siswa->user_id)
                    ->join('exams', 'exams.id', '=', 'exam_sessions.exam_id')
                    ->distinct()
                    ->pluck('exams.rombel_id')
            );
        }

        $ids = $ids->merge(
            Rombel::where('kelas_id', $siswa->kelas_id)
                ->where('tahun_ajaran_id', $siswa->tahun_ajaran_id)
                ->pluck('id')
        );

        return $ids->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{total:int, hadir:int, sakit:int, izin:int, alpa:int, terlambat:int, persen:?float}
     */
    protected static function rekapAbsensi(int $siswaId, int $rombelId, ?int $tahunAjaranId): array
    {
        $baris = Absensi::where('siswa_id', $siswaId)
            ->where('rombel_id', $rombelId)
            ->get(['status', 'tanggal']);

        // Dihitung per tanggal, bukan per baris. Setelah constraint unik
        // dilonggarkan ke (siswa, rombel, tanggal), satu hari bisa punya
        // lebih dari satu baris dan count() akan menggandakan hari itu.
        $perStatus = $baris->groupBy('status')
            ->map(fn ($grup) => $grup->pluck('tanggal')->unique()->count());

        $total = $baris->pluck('tanggal')->unique()->count();
        $hadir = (int) ($perStatus['hadir'] ?? 0) + (int) ($perStatus['terlambat'] ?? 0);

        return [
            'total' => $total,
            'hadir' => (int) ($perStatus['hadir'] ?? 0),
            'sakit' => (int) ($perStatus['sakit'] ?? 0),
            'izin' => (int) ($perStatus['izin'] ?? 0),
            'alpa' => (int) ($perStatus['alpa'] ?? 0),
            'terlambat' => (int) ($perStatus['terlambat'] ?? 0),
            'persen' => $total ? (int) round($hadir / $total * 100) : null,
        ];
    }

    /**
     * Pemecahan per periode (semester ganjil/genap) memakai definisi rentang
     * yang sama dengan rekap absensi agar angka tidak berbeda antar halaman.
     *
     * @return array<string, array{total:int, hadir:int, alpa:int, persen:?float}>
     */
    protected static function rekapPeriode(int $siswaId, int $rombelId, ?int $tahunAjaranId): array
    {
        $hasil = [];

        foreach (['ganjil', 'genap'] as $periode) {
            $rentang = AbsensiController::rentangPeriode($periode, null, $tahunAjaranId);

            $perTanggal = Absensi::where('siswa_id', $siswaId)
                ->where('rombel_id', $rombelId)
                ->whereBetween('tanggal', [$rentang['mulai'], $rentang['selesai']])
                ->get(['status', 'tanggal'])
                ->groupBy('tanggal');

            $hadir = $perTanggal->filter(
                fn ($s) => in_array($s->first()->status, ['hadir', 'terlambat'], true)
            )->count();
            $alpa = $perTanggal->filter(fn ($s) => $s->first()->status === 'alpa')->count();
            $total = $perTanggal->count();

            $hasil[$periode] = [
                'total' => $total,
                'hadir' => $hadir,
                'alpa' => $alpa,
                'persen' => $total ? (int) round($hadir / $total * 100) : null,
            ];
        }

        return $hasil;
    }

    /**
     * Rekap e-learning per mata pelajaran dalam satu rombel.
     *
     * Beban tugas dihitung dari tabel Tugas, bukan dari pengumpulan, supaya
     * tugas yang belum dikerjakan siswa tetap terlihat.
     *
     * @return array<int, array{mapel: ?MataPelajaran, ditugas:int, dikumpul:int, dinilai:int, belumDikumpul:int, rata:?float}>
     */
    protected static function rekapElearning(int $siswaId, int $rombelId): array
    {
        $tugas = Tugas::with('mataPelajaran')
            ->where('rombel_id', $rombelId)
            ->get()
            ->groupBy(fn ($t) => $t->mata_pelajaran_id);

        $semuaIdTugas = $tugas->flatten()->pluck('id');

        // keyBy, bukan groupBy: constraint unik (tugas_id, siswa_id)
        // menjamin paling banyak satu pengumpulan per tugas untuk siswa ini,
        // jadi tidak perlu grup berisi koleksi.
        $kumpul = $semuaIdTugas->isEmpty()
            ? collect()
            : PengumpulanTugas::where('siswa_id', $siswaId)
                ->whereIn('tugas_id', $semuaIdTugas->all())
                ->get()
                ->keyBy('tugas_id');

        $hasil = [];

        foreach ($tugas as $daftarTugas) {
            $idMapelIni = $daftarTugas->pluck('id')->all();

            $dikumpul = $kumpul->filter(
                fn ($p, $idTugas) => in_array($idTugas, $idMapelIni, true)
            )->values();

            $nilai = $dikumpul->filter(fn ($p) => $p->nilai !== null)->pluck('nilai')
                ->map(fn ($v) => (float) $v);

            $hasil[] = [
                'mapel' => $daftarTugas->first()->mataPelajaran,
                'ditugas' => $daftarTugas->count(),
                'dikumpul' => $dikumpul->count(),
                'dinilai' => $nilai->count(),
                'belumDikumpul' => $daftarTugas->count() - $dikumpul->count(),
                'rata' => $nilai->isNotEmpty()
                    ? round($nilai->sum() / $nilai->count(), 1)
                    : null,
            ];
        }

        usort($hasil, fn ($a, $b) => ($a['mapel']?->kode ?? '~') <=> ($b['mapel']?->kode ?? '~'));

        return $hasil;
    }

    /**
     * Rekap nilai CBT siswa pada satu rombel, dikelompokkan per rantai remedial.
     *
     * Satu grup per rantai remedial, sehingga rapor memakai skor terbaik antara ujian asal
     * dan remedial-remedialnya. Ujian yang BUKAN remedial selalu menjadi
     * kelompoknya sendiri: inilah yang menjaga UH-1 dan UH-2 tetap dua nilai
     * yang dihitung, bukan digabung jadi satu.
     *
     * @return array<int, array{
     *     nama:string,
     *     mapel:?string,
     *     skor:?float,
     *     status:string,
     *     jumlahPercobaan:int,
     *     adalahRemedial:bool,
     *     semuaPercobaan:array<int, array{nama:string, skor:?float, status:string}>
     * }>
     */
    protected static function rekapCbt(Siswa $siswa, int $rombelId): array
    {
        if (! $siswa->user_id) {
            return [];
        }

        $sesi = ExamSession::with('exam.mataPelajaran')
            ->where('user_id', $siswa->user_id)
            ->whereHas('exam', fn ($q) => $q->where('rombel_id', $rombelId))
            ->orderByDesc('exam_id')
            ->get();

        $kelompok = [];

        foreach ($sesi as $s) {
            if ($s->exam === null) {
                continue;
            }

            // Kunci kelompok: remedial memakai ujian asalnya sebagai kunci.
            $kunci = $s->exam->nilaiKelompok();

            $kelompok[$kunci][] = $s;
        }

        $hasil = [];

        foreach ($kelompok as $sesiGrup) {
            $sesiGrup = collect($sesiGrup);

            // Skor tertinggi menang. `null` (belum dinilai / diskualifikasi)
            // tidak pernah mengalahkan angka.
            $terbaik = $sesiGrup
                ->filter(fn ($s) => $s->score !== null)
                ->sortByDesc(fn ($s) => (float) $s->score)
                ->first();

            // Kalau tidak satu pun punya skor (semua diskualifikasi), tetap
            // tampilkan yang terakhir agar siswa melihat statusnya.
            $terlihat = $terbaik ?? $sesiGrup->last();

            $semua = $sesiGrup
                ->map(fn ($s) => [
                    'nama' => $s->exam?->name ?? 'Ujian',
                    'skor' => $s->score !== null ? (float) $s->score : null,
                    'status' => $s->status,
                ])
                ->values();

            $hasil[] = [
                'nama' => $terlihat->exam?->name ?? 'Ujian',
                'mapel' => $terlihat->exam?->mataPelajaran?->kode,
                'skor' => $terbaik?->score !== null ? (float) $terbaik->score : null,
                'status' => $terlihat->status,
                'jumlahPercobaan' => $sesiGrup->count(),
                'adalahRemedial' => $sesiGrup->count() > 1,
                'semuaPercobaan' => $semua->all(),
            ];
        }

        return $hasil;
    }

    /**
     * Ringkasan seluruh baris untuk kepala halaman.
     *
     * @param  array<int, array<string, mixed>>  $baris
     * @return array{rombel:int, tahun:int, hariAbsen:int, persenAbsen:?int, tugas:int}
     */
    public static function ringkas(array $baris): array
    {
        $absen = array_column(array_column($baris, 'absensi'), 'total');
        $hadir = array_column(array_column($baris, 'absensi'), 'hadir');
        $totalAbsen = (int) array_sum($absen);
        $totalHadir = (int) array_sum($hadir);

        // `elearning` adalah daftar per mapel di dalam tiap baris, bukan
        // array associative — harus dijumlahkan per iterasi. Dengan
        // array_column dua tingkat hasilnya kosong dan totals selalu nol.
        $totalTugas = 0;
        foreach ($baris as $r) {
            foreach ($r['elearning'] as $m) {
                $totalTugas += (int) $m['ditugas'];
            }
        }

        return [
            'rombel' => count($baris),
            'tahun' => count(array_filter(array_column($baris, 'tahunAjaranId'))),
            'hariAbsen' => $totalAbsen,
            'persenAbsen' => $totalAbsen ? (int) round($totalHadir / $totalAbsen * 100) : null,
            'tugas' => $totalTugas,
        ];
    }

    /**
     * Rombel milik siswa yang boleh diakses user.
     *
     * Guru hanya boleh melihat siswa pada rombel yang diampu — wali atau
     * pengampu mapel — memakai sumber cakupan yang sama dengan modul lain.
     * User yang bukan guru dan bukan admin mendapat kosong, bukan semua.
     */
    public static function bolehAkses(User $user, Siswa $siswa): bool
    {
        return Rombel::terjangkauUser($user)
            ->whereIn('id', static::kumpulkanRombelIds($siswa))
            ->exists();
    }

    /**
     * @return Collection<int>
     */
    public static function rombelIdsTerpakai(Siswa $siswa): Collection
    {
        return collect(static::kumpulkanRombelIds($siswa));
    }
}
