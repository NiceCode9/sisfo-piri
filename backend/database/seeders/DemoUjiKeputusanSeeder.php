<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\PengumpulanTugas;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use App\Models\WaliMurid;
use Illuminate\Database\Seeder;

/**
 * Data untuk menguji tiga perbaikan "keputusan data akademik" secara manual.
 *
 * Yang diuji:
 *  1. Fail-open scoping rekap absensi - guru tanpa penugasan harus melihat NOL
 *     rombel, bukan seluruh rombel.
 *  2. Kunci hapus rombel - rombel yang punya histori harus menolak dihapus dan
 *     menyebut jenis datanya; yang benar-benar kosong harus bisa dihapus.
 *  3. Constraint unik + koreksi riwayat - mengedit baris riwayat supaya bentrok
 *     harus ditolak dengan pesan terbaca.
 *
 * Plus dua skenario pendukung: absensi di tanggal sama pada dua rombel, dan
 * baris riwayat yang sengaja salah supaya ada yang bisa dikoreksi.
 *
 * Skenario yang paling mudah disalahpahami diberi `keterangan` berlabel
 * "SKENARIO UJI" supaya jelas itu disengaja, bukan data rusak. Dua skenario
 * seperti itu:
 *  - absensi tanggal sama di dua rombel (pindah kelas tengah tahun - ini juga
 *    alasan constraint `(siswa, rombel, tanggal)` dilonggarkan);
 *  - satu siswa punya dua baris riwayat di tahun ajaran yang sama.
 *
 * Dijalankan lewat `DatabaseSeeder`, jadi `migrate:fresh --seed` sudah cukup.
 * Dilewati total bila environment production: akun-akun di sini memakai
 * password yang sengaja lemah dan tidak boleh masuk ke server.
 */
class DemoUjiKeputusanSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoUjiKeputusanSeeder dilewati: environment production.');

            return;
        }

        $this->buatGuruTanpaPenugasan();
        $this->buatGuruPengampuSaja();

        $this->buatRombelKosong();
        $this->buatRombelHanyaAbsensi();
        $this->buatRombelHanyaTugas();
        $this->buatRombelHanyaUjian();
        $this->buatRombelCampuran();

        $siswaAbsensi = $this->buatAbsensiDuaRombelSatuTanggal();
        $this->buatRiwayatBentrok();
        $this->buatRiwayatSalah();

        $this->buatAkunUji($siswaAbsensi);

        $this->command?->info('Data uji keputusan akademik siap. Akun uji memakai password: password');
    }

    // ------------------------------------------------------------------
    // Skenario 1 - fail-open scoping
    // ------------------------------------------------------------------

    /**
     * Guru tanpa kelas wali dan tanpa satu pun penugasan mapel.
     *
     * Sebelum diperbaiki, `rombelTerjangkau()` mengembalikan SELURUH rombel
     * untuk guru seperti ini. Sekarang harus nol.
     */
    private function buatGuruTanpaPenugasan(): void
    {
        $user = $this->akun('guru-tanpa-kelas-uji', 'Guru Tanpa Kelas Uji', 'guru');

        Guru::firstOrCreate(
            ['user_id' => $user->id],
            [
                'nip' => '199701011997011001',
                'nama' => 'Guru Tanpa Kelas Uji',
                'jenis_kelamin' => 'L',
                'is_aktif' => true,
            ]
        );
    }

    /**
     * Guru yang mengampu satu mapel di rombel yang BUKAN dia wali.
     *
     * Membuktikan cakupan "wali ATAU pengampu", bukan "wali saja".
     */
    private function buatGuruPengampuSaja(): void
    {
        $rombel = $this->rombel('7D');
        $mapelLain = MataPelajaran::where('kode', 'IPA')->firstOrFail();

        $user = $this->akun('guru-pengampu-uji', 'Guru Pengampu Saja Uji', 'guru');

        $guru = Guru::firstOrCreate(
            ['user_id' => $user->id],
            [
                'nip' => '199702021998022002',
                'nama' => 'Guru Pengampu Saja Uji',
                'jenis_kelamin' => 'P',
                'is_aktif' => true,
            ]
        );

        Pengampu::firstOrCreate([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapelLain->id,
            'rombel_id' => $rombel->id,
        ]);

        // Rombel 7D sengaja TIDAK diberi wali: satu-satunya alasan guru uji ini
        // boleh melihat 7D adalah penugasan mapelnya. Kalau rombel ini punya
        // wali, pengujian ini tidak lagi membuktikan apa pun.
    }

    // ------------------------------------------------------------------
    // Skenario 2 - kunci hapus rombel
    // ------------------------------------------------------------------

    /**
     * Rombel tanpa apa pun: harus BERHASIL dihapus.
     *
     * Tanpa ini, tidak ada jalur sukses yang bisa diuji - karena semua rombel
     * bawaan hasil seeder sudah punya penugasan.
     */
    private function buatRombelKosong(): void
    {
        $kelas = Kelas::firstOrCreate(['nama_kelas' => '7U'], ['tingkat' => '7']);
        $tahun = TahunAjaran::aktif()->firstOrFail();

        Rombel::firstOrCreate([
            'kelas_id' => $kelas->id,
            'tahun_ajaran_id' => $tahun->id,
        ]);
    }

    private function buatRombelHanyaAbsensi(): void
    {
        [$rombel, $siswa] = $this->rombelSiswa('7V', 101);
        $this->absensi($rombel, $siswa, now()->subDays(3)->toDateString(), 'hadir');
    }

    private function buatRombelHanyaTugas(): void
    {
        [$rombel, $siswa] = $this->rombelSiswa('7W', 102);
        $this->tugasDanPengumpulan($rombel, $siswa, 80);
    }

    private function buatRombelHanyaUjian(): void
    {
        [$rombel, $siswa] = $this->rombelSiswa('7X', 103);
        $this->ujian($rombel, $siswa, 90);
    }

    /**
     * Rombel dengan lebih dari satu jenis histori - pesannya harus menyebut
     * semua yang memblokir, bukan hanya yang pertama.
     */
    private function buatRombelCampuran(): void
    {
        [$rombel, $siswa] = $this->rombelSiswa('7Y', 104);
        $this->absensi($rombel, $siswa, now()->subDays(2)->toDateString(), 'hadir');
        $this->tugasDanPengumpulan($rombel, $siswa, 75);
        $this->ujian($rombel, $siswa, 88);
    }

    // ------------------------------------------------------------------
    // Skenario 3 - constraint unik + koreksi riwayat
    // ------------------------------------------------------------------

    /**
     * Satu siswa punya kehadiran pada tanggal SAMA di dua rombel berbeda.
     *
     * Ini skenario pindah kelas tengah tahun, dan sekaligus alasan constraint
     * unik absensi dilonggarkan dari (siswa, tanggal) menjadi (siswa, rombel,
     * tanggal). Rekap harus menghitung hari itu SATU kali, bukan dua.
     */
    private function buatAbsensiDuaRombelSatuTanggal(): ?Siswa
    {
        $tanggal = now()->subWeek()->toDateString();

        [$rombelA, $siswa] = $this->rombelSiswa('7Z', 105);
        $rombelB = $this->rombel('7B');

        // Siswa juga anggota 7B pada tahun yang sama - dipindah di tengah tahun.
        RiwayatKelas::firstOrCreate([
            'siswa_id' => $siswa->id,
            'kelas_id' => $rombelB->kelas_id,
            'tahun_ajaran_id' => $rombelB->tahun_ajaran_id,
        ], [
            'status' => 'pindah',
            'keterangan' => 'SKENARIO UJI: pindah dari 7Z ke 7B di tengah tahun ajaran',
        ]);

        foreach ([[$rombelA, 'hadir'], [$rombelB, 'izin']] as [$rombel, $status]) {
            Absensi::firstOrCreate([
                'siswa_id' => $siswa->id,
                'rombel_id' => $rombel->id,
                'tanggal' => $tanggal,
            ], [
                'status' => $status,
                'metode' => 'manual',
                'keterangan' => 'SKENARIO UJI: satu tanggal, dua rombel. Kalau dihitung dua kali, rekap kehadiran salah.',
            ]);
        }

        return $siswa;
    }

    /**
     * Satu siswa punya DUA baris riwayat pada tahun ajaran yang sama dengan
     * kelas berbeda. Bentuk seperti ini muncul ketika siswa sudah dipindah
     * ke kelas berikutnya lalu dikembalikan.
     *
     * Mengubah salah satu baris supaya menunjuk kelas yang sama dengan baris
     * lain harus DITOLAK dengan pesan yang terbaca, bukan 500 dari constraint.
     *
     * Baris kedua wajib memakai kelas BERBEDA: constraint
     * (siswa, kelas, tahun) justru melarang dua baris untuk kelas yang sama
     * pada tahun yang sama.
     */
    private function buatRiwayatBentrok(): void
    {
        $tahun = TahunAjaran::aktif()->firstOrFail();
        $rombelLain = $this->rombel('7D');

        // rombelSiswa menulis baris pertama: (siswa, 7C, tahun aktif).
        [$siswa] = $this->rombelSiswa('7C', 106);

        RiwayatKelas::firstOrCreate([
            'siswa_id' => $siswa->id,
            'kelas_id' => $rombelLain->kelas_id,
            'tahun_ajaran_id' => $tahun->id,
        ], [
            'status' => 'mengulang',
            'keterangan' => 'SKENARIO UJI: baris kedua, kelas berbeda pada tahun yang sama. '
                .'Ubah kelasnya menjadi 7C lewat UI, harus ditolak dan bukan 500.',
        ]);
    }

    /**
     * Satu siswa yang punya baris riwayat di tahun ajaran yang tidak sesuai
     * dengan kelas tempat ia sekarang, supaya ada yang bisa dikoreksi.
     */
    private function buatRiwayatSalah(): void
    {
        $tahunSekarang = TahunAjaran::aktif()->firstOrFail();
        $tahunLama = TahunAjaran::where('status_aktif', false)
            ->where('id', '!=', $tahunSekarang->id)
            ->orderByDesc('tanggal_mulai')
            ->first();

        if (! $tahunLama) {
            return;
        }

        $rombelLama = $this->rombel('7B');

        // rombelSiswa menulis baris yang benar: (siswa, 7A, tahun sekarang).
        [$siswa] = $this->rombelSiswa('7A', 107);

        // Baris yang tidak sesuai: tahun ajaran yang keliru. Inilah yang
        // harus dikoreksi admin lewat menu Riwayat Kelas.
        RiwayatKelas::firstOrCreate([
            'siswa_id' => $siswa->id,
            'kelas_id' => $rombelLama->kelas_id,
            'tahun_ajaran_id' => $tahunLama->id,
        ], [
            'status' => 'mengulang',
            'keterangan' => 'SKENARIO UJI: tahun ajaran tidak sesuai, koreksi lewat menu Riwayat Kelas.',
        ]);
    }

    // ------------------------------------------------------------------
    // Akun uji
    // ------------------------------------------------------------------

    /**
     * Akun dengan password seragam supaya mudah diketik saat menguji.
     * Password lemah ini hanya aman karena seeder ini dilewati di produksi.
     */
    private function buatAkunUji(?Siswa $siswaSkenario = null): void
    {
        $this->akun('admin-uji', 'Admin Uji', 'admin');
        $this->akun('guru-wali-uji', 'Guru Wali Uji', 'guru');

        if ($siswaSkenario) {
            $this->akunWaliDariSiswa($siswaSkenario);
        }
    }

    /**
     * Pastikan ada akun wali murid untuk siswa skenario absensi dua rombel,
     * supaya halamannya bisa dibuka tanpa harus mencari tahu akun mana.
     *
     * `SiswaObserver` sudah membuat `WaliMurid` + user-nya saat siswa dibuat,
     * jadi biasanya cabang pertama yang dipakai.
     */
    private function akunWaliDariSiswa(Siswa $siswa): void
    {
        if (WaliMurid::where('siswa_id', $siswa->id)->exists()) {
            return;
        }

        $this->akun('ortu-uji', 'Wali Murid Uji', 'orang-tua');
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    private function akun(string $username, string $nama, ?string $peran): User
    {
        $user = User::firstOrCreate(
            ['username' => $username],
            [
                'name' => $nama,
                'email' => $username.'@uji.example.com',
                'password' => 'password',
            ]
        );

        if ($peran) {
            $user->assignRole($peran);
        }

        return $user;
    }

    /**
     * Rombel untuk nama kelas tertentu.
     *
     * Dibuat bila belum ada. `DataMasterRealSeeder` hanya membuat kelas 7A–9C,
     * sedangkan skenario ini memakai 7D sebagai "rombel milik orang lain".
     * `firstOrFail()` di sini dulu tidak masalah karena `AkademikSeeder`
     * membuat 7A–7D; sekarang kelas itu harus dibuat sendiri supaya seeder ini
     * tidak bergantung pada tebakan isi data master.
     */
    private function rombel(string $kelas): Rombel
    {
        $tahun = TahunAjaran::aktif()->firstOrFail();
        $kelasModel = Kelas::firstOrCreate(['nama_kelas' => $kelas], ['tingkat' => substr($kelas, 0, 1)]);

        return Rombel::firstOrCreate([
            'kelas_id' => $kelasModel->id,
            'tahun_ajaran_id' => $tahun->id,
        ]);
    }

    /**
     * Rombel dengan satu siswa, untuk skenario hapus rombel.
     *
     * Rombel sengaja dibuat TANPA pengampus supaya tidak ada yang memblokir
     * penghapusan selain histori yang memang diuji.
     *
     * @param  int  $kode  penanda unik skenario, jadi nisn selalu numerik
     * @return array{Rombel, Siswa}
     */
    private function rombelSiswa(string $kelas, int $kode): array
    {
        $tahun = TahunAjaran::aktif()->firstOrFail();
        $kelasModel = Kelas::firstOrCreate(['nama_kelas' => $kelas], ['tingkat' => '7']);

        $rombel = Rombel::firstOrCreate([
            'kelas_id' => $kelasModel->id,
            'tahun_ajaran_id' => $tahun->id,
        ]);

        // NISN harus tepat 10 digit angka. Kode di sini bukan string bebas:
        // `str_pad` mengembalikan string utuh kalau sudah lebih panjang dari
        // panjang pad, sehingga penanda Huruf seperti "7Z-A" akan bocor ke
        // nisn dan jadi tidak valid.
        $nisn = '00808'.str_pad((string) $kode, 5, '0', STR_PAD_LEFT);

        $user = $this->akun($nisn, "Murid Uji {$kelas}-{$kode}", 'siswa');

        $siswa = Siswa::firstOrCreate(['nisn' => $nisn], [
            'user_id' => $user->id,
            'nis' => '60'.substr($nisn, -4),
            'tahun_ajaran_id' => $tahun->id,
            'kelas_id' => $kelasModel->id,
            'is_aktif' => true,
            'nama_ayah' => "Wali Uji {$kelas}-{$kode}",
            'no_hp_orang_tua' => '08137'.substr($nisn, -5),
        ]);

        RiwayatKelas::firstOrCreate([
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelasModel->id,
            'tahun_ajaran_id' => $tahun->id,
        ], ['status' => 'aktif']);

        return [$rombel, $siswa];
    }

    private function absensi(Rombel $rombel, Siswa $siswa, string $tanggal, string $status): void
    {
        Absensi::firstOrCreate([
            'siswa_id' => $siswa->id,
            'rombel_id' => $rombel->id,
            'tanggal' => $tanggal,
        ], ['status' => $status, 'metode' => 'manual']);
    }

    private function tugasDanPengumpulan(Rombel $rombel, Siswa $siswa, int $nilai): void
    {
        $tugas = Tugas::firstOrCreate([
            'rombel_id' => $rombel->id,
            'mata_pelajaran_id' => MataPelajaran::aktif()->firstOrFail()->id,
            'judul' => 'Tugas Uji Hapus Rombel',
        ], ['is_aktif' => true]);

        PengumpulanTugas::firstOrCreate([
            'tugas_id' => $tugas->id,
            'siswa_id' => $siswa->id,
        ], ['nilai' => $nilai]);
    }

    private function ujian(Rombel $rombel, Siswa $siswa, float $skor): void
    {
        $exam = Exam::firstOrCreate([
            'rombel_id' => $rombel->id,
            'name' => 'Ujian Uji Hapus Rombel',
        ], [
            'mata_pelajaran_id' => MataPelajaran::aktif()->firstOrFail()->id,
            'duration_minutes' => 60,
            'status' => 'published',
            'created_by' => $siswa->user_id,
        ]);

        $token = ExamToken::firstOrCreate([
            'exam_id' => $exam->id,
            'token' => 'uji-hapus-rombel-'.$exam->id,
        ], [
            'active_from' => now()->subDay(),
            'active_until' => now()->addDay(),
            'max_usage' => 10,
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
            'started_at' => now()->subHour(),
            'expected_end_at' => now(),
            'finished_at' => now(),
        ]);
    }
}
