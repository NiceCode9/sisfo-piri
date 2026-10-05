<?php

namespace Database\Seeders;

use App\Actions\Rombel\SalinRombelAction;
use App\Actions\Siswa\ProsesKenaikanAction;
use App\Models\Absensi;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Riwayat dua tahun ajaran untuk halaman riwayat terpadu.
 *
 * Dev DB hanya punya satu tahun ajaran dengan data contoh, sehingga
 * rekonstruksi riwayat lintas tahun tidak punya apa pun untuk ditampilkan:
 * absensi 0 baris, `exam_sessions` 0 baris, seluruh riwayat kelas di satu
 * tahun. Tanpa seeder ini halaman riwayat terpadu hanya akan pernah tampil
 * dengan satu blok kosong — persis kondisi yang sudah berjalan, bukan yang
 * mau diuji.
 *
 * Data duas tahun ini DIBUAT dengan memanggil `SalinRombelAction` dan
 * `ProsesKenaikanAction`, bukan ditulis tangan. Alasannya: hasilnya dijamin
 * identik dengan yang dihasilkan wizard "Tahun Ajaran Baru", dan seeder ini
 * sekaligus menjadi uji integrasi kedua Action itu.
 *
 * Versi sebelumnya memakai `$siswa->update(['kelas_id' => ...])` langsung
 * tanpa menulis baris `RiwayatKelas`. Akibatnya `siswas.kelas_id` menunjuk
 * kelas baru sementara `Rombel::anggotaIdsAktif()` untuk kelas itu tetap
 * kosong — siswa tidak muncul di grid absensi, gate scan QR, maupun rekap
 * nilai. Dua sumber "kelas sekarang" disagreed. Memakai Action menutup
 * celah itu karena baris riwayatnya ikut tertulis.
 */
class RiwayatMultiTahunSeeder extends Seeder
{
    /** Prefix NISN, supaya mudah dikenali dan tidak bentrok dengan seeder lain. */
    private const PREFIX_NISN = '00807';

    /** Penghitung agar NISN selalu numerik 10 digit dan unik. */
    private int $urut = 0;

    public function run(): void
    {
        $tahunPertama = TahunAjaran::aktif()->firstOrFail();
        $tahunKedua = TahunAjaran::firstOrCreate(
            ['nama_tahun_ajaran' => '2027/2028'],
            ['tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'status_aktif' => false]
        );

        // Kelas lanjutan sudah dibuat `DemoKenaikanSeeder`; firstOrCreate
        // supaya seeder ini juga bisa berdiri sendiri.
        $kelas8A = Kelas::firstOrCreate(['nama_kelas' => '8A'], ['tingkat' => '8']);
        $kelas8B = Kelas::firstOrCreate(['nama_kelas' => '8B'], ['tingkat' => '8']);

        $rombel7A = $this->rombel($tahunPertama, '7A');
        $rombel7B = $this->rombel($tahunPertama, '7B');

        $siswas = $this->buatSiswa('7A', $rombel7A, 3);
        $this->buatSiswa('7B', $rombel7B, 2);

        // Langkah 1 — salin rombel, wali, dan penugasan ke tahun kedua.
        app(SalinRombelAction::class)->jalankan($tahunPertama->id, $tahunKedua->id);

        // Wizard "Tahun Ajaran Baru" membuat rombel kosong untuk kelas tujuan
        // yang tidak punya sumber untuk disalin. Di sini dilakukan manual
        // supaya tidak memicu guard yang sama.
        Rombel::firstOrCreate(['kelas_id' => $kelas8A->id, 'tahun_ajaran_id' => $tahunKedua->id]);
        Rombel::firstOrCreate(['kelas_id' => $kelas8B->id, 'tahun_ajaran_id' => $tahunKedua->id]);

        // Langkah 2 — pindahkan siswa lewat Action, bukan update manual,
        // supaya `RiwayatKelas` ikut tertulis dan keanggotaan rombel benar.
        app(ProsesKenaikanAction::class)->jalankan(
            $tahunPertama->id,
            $tahunKedua->id,
            [
                $rombel7A->kelas_id => (string) $kelas8A->id,
                $rombel7B->kelas_id => (string) $kelas8B->id,
            ]
        );

        $mapel = MataPelajaran::aktif()->firstOrFail();

        foreach ($siswas as $baris) {
            [$siswa, $rombelAsal] = $baris;

            // Rombel tujuan dibaca dari kelas yang sedang ditunjuk siswa
            // setelah kenaikan, bukan dari daftar tebakan — kalau tebakan
            // meleset, data tahun kedua akan menempel ke rombel tahun pertama
            // dan riwayat terpadu menampilkan angka yang salah.
            $setelahNaik = $siswa->fresh();
            $rombelBaru = Rombel::where('kelas_id', $setelahNaik->kelas_id)
                ->where('tahun_ajaran_id', $setelahNaik->tahun_ajaran_id)
                ->firstOrFail();

            $this->isiTahunPertama($baris, $rombelAsal, $mapel);
            $this->isiTahunKedua($baris, $rombelBaru, $mapel);
        }

        $this->command?->info('Riwayat dua tahun ajaran siap (7A→8A, 7B→8B).');
    }

    private function rombel(TahunAjaran $tahun, string $kelas): Rombel
    {
        return Rombel::where('tahun_ajaran_id', $tahun->id)
            ->whereHas('kelas', fn ($q) => $q->where('nama_kelas', $kelas))
            ->firstOrFail();
    }

    /**
     * @return array<int, array{Siswa, Rombel, int}>
     */
    private function buatSiswa(string $kelas, Rombel $rombel, int $jumlah): array
    {
        $hasil = [];

        for ($i = 1; $i <= $jumlah; $i++) {
            // Penomoran sederhana, bukan crc32 kelas: hasilnya harus selalu
            // numerik 10 digit karena NISN tidak boleh mengandung huruf.
            $this->urut++;
            $nisn = self::PREFIX_NISN.str_pad((string) $this->urut, 5, '0', STR_PAD_LEFT);

            $user = User::firstOrCreate(
                ['username' => $nisn],
                [
                    'name' => "Siswa Riwayat {$kelas}-{$i}",
                    'email' => "riwayat-{$kelas}-{$i}@example.com",
                    'password' => $nisn,
                ]
            );
            $user->assignRole('siswa');

            $siswa = Siswa::firstOrCreate(
                ['nisn' => $nisn],
                [
                    'user_id' => $user->id,
                    'nis' => '7'.substr($nisn, -5),
                    'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
                    'kelas_id' => $rombel->kelas_id,
                    'tanggal_diterima' => now()->subYear()->toDateString(),
                    'is_aktif' => true,
                    'nama_ayah' => "Ayah Riwayat {$kelas}",
                    'nama_ibu' => "Ibu Riwayat {$kelas}",
                    'no_hp_orang_tua' => '0813777'.substr($nisn, -4),
                ]
            );

            RiwayatKelas::firstOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $rombel->kelas_id,
                    'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
                ],
                ['status' => 'aktif']
            );

            $hasil[] = [$siswa->fresh(), $rombel, $i];
        }

        return $hasil;
    }

    /**
     * September tahun pertama (semester ganjil tahun pertama), tiga hari.
     */
    private function isiTahunPertama(array $baris, Rombel $rombel, MataPelajaran $mapel): void
    {
        [$siswa, , $urut] = $baris;

        foreach (['hadir', 'hadir', 'sakit'] as $offset => $status) {
            Absensi::firstOrCreate([
                'siswa_id' => $siswa->id,
                'rombel_id' => $rombel->id,
                'tanggal' => date('Y-m-d', strtotime('2025-09-0'.($offset + 1))),
            ], [
                'status' => $status,
                'metode' => 'manual',
                'keterangan' => 'Riwayat dua tahun (semester ganjil tahun pertama)',
            ]);
        }

        $tugas = Tugas::firstOrCreate([
            'rombel_id' => $rombel->id,
            'mata_pelajaran_id' => $mapel->id,
            'judul' => 'Tugas Ganjil '.$rombel->kelas->nama_kelas.'-'.$urut,
        ], ['is_aktif' => true]);

        PengumpulanTugas::firstOrCreate(
            ['tugas_id' => $tugas->id, 'siswa_id' => $siswa->id],
            ['jawaban_text' => 'Jawaban semester ganjil', 'nilai' => 70 + $urut * 5]
        );

        $this->buatUjian($rombel, $siswa, $mapel, 'Ujian Ganjil '.$rombel->kelas->nama_kelas, 65 + $urut * 5);
    }

    /**
     * Maret tahun kedua (semester genap tahun kedua).
     */
    private function isiTahunKedua(array $baris, Rombel $rombel, MataPelajaran $mapel): void
    {
        [$siswa, , $urut] = $baris;

        foreach (['hadir', 'terlambat', 'hadir', 'alpa'] as $offset => $status) {
            Absensi::firstOrCreate([
                'siswa_id' => $siswa->id,
                'rombel_id' => $rombel->id,
                'tanggal' => date('Y-m-d', strtotime('2027-03-0'.($offset + 1))),
            ], [
                'status' => $status,
                'metode' => 'manual',
                'keterangan' => 'Riwayat dua tahun (semester genap tahun kedua)',
            ]);
        }

        foreach ([80 + $urut * 3, 88 + $urut * 3] as $nilai) {
            $tugas = Tugas::firstOrCreate([
                'rombel_id' => $rombel->id,
                'mata_pelajaran_id' => $mapel->id,
                'judul' => 'Tugas Genap '.$rombel->kelas->nama_kelas.'-'.$nilai,
            ], ['is_aktif' => true]);

            PengumpulanTugas::firstOrCreate(
                ['tugas_id' => $tugas->id, 'siswa_id' => $siswa->id],
                ['nilai' => $nilai]
            );
        }

        // Satu tugas sengaja tidak dikumpulkan supaya kolom "belum" terlihat
        // di riwayat.
        Tugas::firstOrCreate([
            'rombel_id' => $rombel->id,
            'mata_pelajaran_id' => $mapel->id,
            'judul' => 'Tugas Genap '.$rombel->kelas->nama_kelas.' belum dikerjakan',
        ], ['is_aktif' => true]);

        $this->buatUjian($rombel, $siswa, $mapel, 'Ujian Genap '.$rombel->kelas->nama_kelas, 82 + $urut * 3);
    }

    private function buatUjian(Rombel $rombel, Siswa $siswa, MataPelajaran $mapel, string $nama, float $skor): void
    {
        $exam = Exam::firstOrCreate([
            'rombel_id' => $rombel->id,
            'name' => $nama,
        ], [
            'mata_pelajaran_id' => $mapel->id,
            'duration_minutes' => 60,
            'status' => 'published',
            'created_by' => $siswa->user_id,
        ]);

        $token = ExamToken::firstOrCreate([
            'exam_id' => $exam->id,
            'token' => strtolower(str_replace(' ', '', $nama)).'-'.$exam->id,
        ], [
            'active_from' => now()->subYear(),
            'active_until' => now()->addYear(),
            'max_usage' => 100,
            'used_count' => 1,
            'is_active' => false,
            'created_by' => $siswa->user_id,
        ]);

        ExamSession::firstOrCreate([
            'exam_id' => $exam->id,
            'exam_token_id' => $token->id,
            'user_id' => $siswa->user_id,
        ], [
            'status' => 'finished',
            'score' => $skor,
            'started_at' => now()->subYear(),
            'expected_end_at' => now()->subYear()->addHour(),
            'finished_at' => now()->subYear()->addHour(),
        ]);
    }
}
