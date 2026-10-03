<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder riwayat dua tahun ajaran.
 *
 * Dev DB hanya punya satu tahun ajaran dengan data contoh, sehingga
 * rekonstruksi riwayat lintas tahun tidak punya apa pun untuk diuji:
 * diuji: absensi 0 baris, exam_sessions 0 baris, seluruh riwayat kelas di
 * tahun yang sama. Tanpa seeder ini, halaman riwayat terpadu hanya akan
 * pernah tampil dengan satu blok yang kosong — persis kasus yang sudah
 * berjalan sekarang, bukan yang mau diuji.
 *
 * Sengaja hanya menambah tahun ajaran kedua dan mengisinya; tahun pertama
 * dan semua master data lain dibiarkan seperti hasil seeder sebelumnya.
 */
class RiwayatMultiTahunSeeder extends Seeder
{
    public function run(): void
    {
        $tahunPertama = TahunAjaran::aktif()->firstOrFail();
        $rombelPertama = Rombel::where('tahun_ajaran_id', $tahunPertama->id)
            ->whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
            ->firstOrFail();

        $siswa = $rombelPertama->anggotaIds()[0] ?? null;

        if ($siswa === null) {
            $this->command?->warn('Tidak ada siswa di rombel 7A tahun pertama — riwayat multi-tahun dilewati.');

            return;
        }

        $modelSiswa = Siswa::with('user')->findOrFail($siswa);

        $tahunKedua = $this->buatTahunKedua($tahunPertama);
        $kelas8A = Kelas::firstOrCreate(['nama_kelas' => '8A'], ['tingkat' => '8']);
        $rombelKedua = Rombel::firstOrCreate(
            ['kelas_id' => $kelas8A->id, 'tahun_ajaran_id' => $tahunKedua->id]
        );

        $modelSiswa->update(['kelas_id' => $kelas8A->id, 'tahun_ajaran_id' => $tahunKedua->id]);

        $this->isiTahunPertama($rombelPertama, $modelSiswa, $tahunPertama);
        $this->isiTahunKedua($rombelKedua, $modelSiswa, $tahunKedua);

        $this->command?->info("Riwayat dua tahun untuk {$modelSiswa->user->name} ({$modelSiswa->nisn}) siap diuji.");
    }

    protected function buatTahunKedua(TahunAjaran $tahunPertama): TahunAjaran
    {
        $mulai = date('Y-m-d', strtotime($tahunPertama->tanggal_mulai.' +1 year'));
        $selesai = date('Y-m-d', strtotime($tahunPertama->tanggal_selesai.' +1 year'));

        return TahunAjaran::firstOrCreate(
            ['nama_tahun_ajaran' => $mulai.'/'.substr($selesai, 2, 2)],
            ['tanggal_mulai' => $mulai, 'tanggal_selesai' => $selesai, 'status_aktif' => false]
        );
    }

    /**
     * Tahun pertama: kehadiran, satu tugas sudah dinilai, satu ujian.
     */
    protected function isiTahunPertama(Rombel $rombel, Siswa $siswa, TahunAjaran $tahun): void
    {
        $this->isiAbsensi($rombel, $siswa, $tahun, '2026-02-02', [
            'hadir', 'hadir', 'sakit', 'hadir', 'alpa',
        ]);

        $mapel = MataPelajaran::aktif()->firstOrFail();
        $tugas = Tugas::create([
            'rombel_id' => $rombel->id,
            'mata_pelajaran_id' => $mapel->id,
            'judul' => 'Tugas Semester Ganjil 7A',
            'is_aktif' => true,
        ]);
        PengumpulanTugas::create([
            'tugas_id' => $tugas->id,
            'siswa_id' => $siswa->id,
            'jawaban_text' => 'J semester ganjil',
            'nilai' => 78,
        ]);

        $this->buatSesiUjian($rombel, $siswa, $mapel, 'Ujian Ganjil 7A', 71.5);
    }

    /**
     * Tahun kedua: kehadiran di semester genap, tugas lebih banyak, satu
     * belum dikumpulkan, satu ujian dengan skor lebih tinggi.
     */
    protected function isiTahunKedua(Rombel $rombel, Siswa $siswa, TahunAjaran $tahun): void
    {
        $this->isiAbsensi($rombel, $siswa, $tahun, '2027-03-01', [
            'hadir', 'terlambat', 'hadir', 'hadir', 'izin',
        ]);

        $mapel = MataPelajaran::aktif()->firstOrFail();

        $tugas1 = Tugas::create([
            'rombel_id' => $rombel->id,
            'mata_pelajaran_id' => $mapel->id,
            'judul' => 'Tugas Genap 8A-1',
            'is_aktif' => true,
        ]);
        PengumpulanTugas::create([
            'tugas_id' => $tugas1->id,
            'siswa_id' => $siswa->id,
            'nilai' => 84,
        ]);

        $tugas2 = Tugas::create([
            'rombel_id' => $rombel->id,
            'mata_pelajaran_id' => $mapel->id,
            'judul' => 'Tugas Genap 8A-2',
            'is_aktif' => true,
        ]);
        PengumpulanTugas::create([
            'tugas_id' => $tugas2->id,
            'siswa_id' => $siswa->id,
            'nilai' => 90,
        ]);

        // Dibuat sengaja tanpa pengumpulan: harus muncul sebagai "belum".
        Tugas::create([
            'rombel_id' => $rombel->id,
            'mata_pelajaran_id' => $mapel->id,
            'judul' => 'Tugas Genap 8A-3 Belum Dikumpulkan',
            'is_aktif' => true,
        ]);

        $this->buatSesiUjian($rombel, $siswa, $mapel, 'Ujian Genap 8A', 88.0);
    }

    /**
     * @param  array<int, string>  $status
     */
    protected function isiAbsensi(Rombel $rombel, Siswa $siswa, TahunAjaran $tahun, string $awal, array $status): void
    {
        foreach ($status as $i => $s) {
            $tanggal = date('Y-m-d', strtotime($awal.' +'.($i * 2).' days'));

            Absensi::firstOrCreate([
                'siswa_id' => $siswa->id,
                'rombel_id' => $rombel->id,
                'tanggal' => $tanggal,
            ], [
                'status' => $s,
                'metode' => 'manual',
            ]);
        }
    }

    protected function buatSesiUjian(Rombel $rombel, Siswa $siswa, MataPelajaran $mapel, string $nama, float $skor): void
    {
        $exam = Exam::create([
            'rombel_id' => $rombel->id,
            'mata_pelajaran_id' => $mapel->id,
            'name' => $nama,
            'duration_minutes' => 60,
            'status' => 'published',
            'created_by' => User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->value('id')
                ?? User::query()->value('id'),
        ]);

        $token = ExamToken::create([
            'exam_id' => $exam->id,
            // Token unik global, jadi nama ujian saja tidak cukup antar ujian.
            'token' => strtolower(str_replace(' ', '', $nama)).'-'.$exam->id,
            'active_from' => now()->subDay(),
            'active_until' => now()->addDay(),
            'max_usage' => 100,
            'used_count' => 1,
            'is_active' => false,
            'created_by' => User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->value('id')
                ?? User::query()->value('id'),
        ]);

        ExamSession::create([
            'exam_id' => $exam->id,
            'exam_token_id' => $token->id,
            'user_id' => $siswa->user_id,
            'status' => 'finished',
            'score' => $skor,
            'started_at' => now()->subHour(),
            'expected_end_at' => now()->addHour(),
            'finished_at' => now(),
        ]);
    }
}
