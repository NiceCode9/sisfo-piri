<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data master yang terlihat seperti sekolah sungguhan.
 *
 * Berbeda dengan `AkademikSeeder` — yang tetap hidup sebagai fixture test —
 * seeder ini dipakai `DatabaseSeeder` supaya data development enak dibaca
 * dan dibaca orang: nama guru dan siswa sungguhan, kelas 7A sampai 9C, 9 rombel
 * dengan wali, dan 90 siswa. Tujuannya menguji UI dengan data yang mirip
 * keadaan nyata, bukan memeriksa apakah 5 baris fixture bisa di-query.
 *
 * Kontrak yang harus dipenuhi seeder ini karena `DatabaseSeeder` memanggil
 * seeder demo setelahnya:
 *  - ada tahun ajaran aktif
 *  - ada rombel di tahun aktif (DemoElearning, DemoCbt)
 *  - ada ≥2 guru aktif (DemoCbt)
 *  - kode mapel MTK / IPA / BIN / BIG ada (DemoCbt, DemoUjiKeputusan)
 *  - kelas 7A, 7B ada (DemoKenaikan, RiwayatMultiTahun, DemoUjiKeputusan)
 *
 * Semua baris memakai `firstOrCreate` dengan kunci unik, jadi `db:seed` bisa
 * dijalankan berulang — termasuk di produksi saat `migrate --force --seed`.
 *
 * Tabel `wali_kelas` sengaja tidak disentuh: sudah deprecated, dan sumber
 * wali yang dibaca sistem adalah `rombels.wali_guru_id` (lihat
 * `docs/PANDUAN_UJI_KEPUTUSAN_AKADEMIK.md`).
 */
class DataMasterRealSeeder extends Seeder
{
    /** Kode mapel yang wajib ada karena dipakai seeder demo. */
    private const MAPEL_WAJIB = ['MTK', 'IPA', 'BIN', 'BIG'];

    /**
     * 12 mapel Kurikulum Merdeka untuk jenjang SMP/SMK.
     *
     * @var list<array{kode:string, nama:string, kelompok:string, kkm:int}>
     */
    private const MAPEL = [
        ['kode' => 'MTK', 'nama' => 'Matematika', 'kelompok' => 'A', 'kkm' => 75],
        ['kode' => 'IPA', 'nama' => 'Ilmu Pengetahuan Alam', 'kelompok' => 'A', 'kkm' => 75],
        ['kode' => 'IPS', 'nama' => 'Ilmu Pengetahuan Sosial', 'kelompok' => 'A', 'kkm' => 75],
        ['kode' => 'BIN', 'nama' => 'Bahasa Indonesia', 'kelompok' => 'A', 'kkm' => 75],
        ['kode' => 'BIG', 'nama' => 'Bahasa Inggris', 'kelompok' => 'A', 'kkm' => 75],
        ['kode' => 'PJOK', 'nama' => 'Pendidikan Jasmani dan Olahkana', 'kelompok' => 'B', 'kkm' => 75],
        ['kode' => 'PKN', 'nama' => 'Pendidikan Pancasila', 'kelompok' => 'A', 'kkm' => 75],
        ['kode' => 'PRA', 'nama' => 'Prakarya', 'kelompok' => 'B', 'kkm' => 75],
        ['kode' => 'SEN', 'nama' => 'Seni Budaya', 'kelompok' => 'B', 'kkm' => 75],
        ['kode' => 'PKWU', 'nama' => 'Pendampingan Kehidupan Rumah', 'kelompok' => 'B', 'kkm' => 75],
        ['kode' => 'TIK', 'nama' => 'Informatika', 'kelompok' => 'B', 'kkm' => 75],
        ['kode' => 'MPL', 'nama' => 'Mata Pelajaran Pilihan', 'kelompok' => 'C', 'kkm' => 75],
    ];

    /** Kelas aktif: 7A/7B/7C, 8A/8B/8C, 9A/9B/9C. */
    private const KELAS = [
        '7A' => '7', '7B' => '7', '7C' => '7',
        '8A' => '8', '8B' => '8', '8C' => '8',
        '9A' => '9', '9B' => '9', '9C' => '9',
    ];

    /**
     * 15 guru. Gender dan NIP realistis — bukan "Guru A" seperti di fixture.
     * `kode` dipakai untuk username yang bisa diketik manusia saat menguji.
     *
     * @var list<array{kode:string, nip:string, nama:string, gender:string, telepon:string}>
     */
    private const GURU = [
        ['kode' => 'santoso', 'nip' => '197203151995031002', 'nama' => 'Budi Santoso, S.Pd.', 'gender' => 'L', 'telepon' => '081234560101'],
        ['kode' => 'hartati', 'nip' => '197608211998032003', 'nama' => 'Siti Hartati, S.Pd.', 'gender' => 'P', 'telepon' => '081234560102'],
        ['kode' => 'pratama', 'nip' => '198011051997041004', 'nama' => 'Andi Pratama, S.Pd.', 'gender' => 'L', 'telepon' => '081234560103'],
        ['kode' => 'lestari', 'nip' => '198304171999062005', 'nama' => 'Dewi Lestari, S.Pd.', 'gender' => 'P', 'telepon' => '081234560104'],
        ['kode' => 'wibowo', 'nip' => '197512091994071006', 'nama' => 'Agus Wibowo, S.Pd.', 'gender' => 'L', 'telepon' => '081234560105'],
        ['kode' => 'ramadhani', 'nip' => '198806231999082007', 'nama' => 'Rina Ramadhani, S.Pd.', 'gender' => 'P', 'telepon' => '081234560106'],
        ['kode' => 'setiawan', 'nip' => '197909141996113008', 'nama' => 'Joko Setiawan, S.Pd.I.', 'gender' => 'L', 'telepon' => '081234560107'],
        ['kode' => 'permata', 'nip' => '198501281997122009', 'nama' => 'Ani Permata, S.Pd.', 'gender' => 'P', 'telepon' => '081234560108'],
        ['kode' => 'nugroho', 'nip' => '197702031993021010', 'nama' => 'Bambang Nugroho, S.Pd.', 'gender' => 'L', 'telepon' => '081234560109'],
        ['kode' => 'safitri', 'nip' => '199007162000032011', 'nama' => 'Maya Safitri, S.Pd.', 'gender' => 'P', 'telepon' => '081234560110'],
        ['kode' => 'kurniawan', 'nip' => '198412051998041012', 'nama' => 'Eko Kurniawan, S.Kom.', 'gender' => 'L', 'telepon' => '081234560111'],
        ['kode' => 'hidayah', 'nip' => '199203102001052013', 'nama' => 'Fitri Hidayah, S.Pd.', 'gender' => 'P', 'telepon' => '081234560112'],
        ['kode' => 'prasetyo', 'nip' => '198806152000062014', 'nama' => 'Hendra Prasetyo, S.Pd.', 'gender' => 'L', 'telepon' => '081234560113'],
        ['kode' => 'maulida', 'nip' => '199109242001072015', 'nama' => 'Nina Maulida, S.Pd.', 'gender' => 'P', 'telepon' => '081234560114'],
        ['kode' => 'gunawan', 'nip' => '198203181999083016', 'nama' => 'Dedi Gunawan, S.Pd.', 'gender' => 'L', 'telepon' => '081234560115'],
    ];

    /** 45 nama depan laki-laki dan 45 perempuan, tanpa tumpang tindih. */
    private const NAMA_DEPAN_L = [
        'Budi', 'Andi', 'Agus', 'Joko', 'Bambang', 'Eko', 'Hendra', 'Dedi', 'Rizki', 'Fajar',
        'Dimas', 'Bayu', 'Galih', 'Arif', 'Teguh', 'Iwan', 'Surya', 'Hadi', 'Rudi', 'Yoga',
        'Bagus', 'Adi', 'Nanda', 'Reza', 'Dewa', 'Ilham', 'Rangga', 'Slamet', 'Wahyu', 'Edi',
        'Agung', 'Erick', 'Rizky', 'Deni', 'Hanafi', 'Krisna', 'Lukman', 'Miko', 'Nanda', 'Oscar',
        'Panji', 'Rendi', 'Slamet', 'Tommy', 'Umar',
    ];

    private const NAMA_DEPAN_P = [
        'Siti', 'Dewi', 'Rina', 'Ani', 'Maya', 'Fitri', 'Nina', 'Indah', 'Putri', 'Wulan',
        'Ayu', 'Bunga', 'Citra', 'Dina', 'Elsa', 'Farida', 'Gita', 'Hesti', 'Intan', 'Jihan',
        'Kartika', 'Lina', 'Martha', 'Nadia', 'Olivia', 'Puspita', 'Ratna', 'Sri', 'Tiur', 'Umi',
        'Vina', 'Wulan', 'Yuni', 'Zahra', 'Endah', 'Hana', 'Ika', 'Juli', 'Lestari', 'Maya',
        'Nisa', 'Oktaviani', 'Puji', 'Ratih', 'Suriani',
    ];

    /** 45 nama belakang. */
    private const NAMA_BELAKANG = [
        'Saputra', 'Wijaya', 'Kusuma', 'Pratama', 'Santoso', 'Hidayat', 'Nugroho', 'Permana', 'Setiawan', 'Halim',
        'Pranoto', 'Utama', 'Wardana', 'Adi', 'Baskoro', 'Cahyani', 'Damar', 'Efendi', 'Gunawan', 'Hardiman',
        'Iskandar', 'Jatmiko', 'Krisna', 'Lazuardi', 'Mahendra', 'Nasution', 'Oktavianto', 'Pranata', 'Rakhmat', 'Santoso',
        'Taufik', 'Utami', 'Wibisono', 'Yulianto', 'Zulkifli', 'Anwar', 'Bambang', 'Chandra', 'Darmawan', 'Effendi',
        'Firmansyah', 'Gunawan', 'Hidayat', 'Indrawan', 'Junaidi',
    ];

    /** Prefiks operator seluler Indonesia yang dipakai untuk nomor orang tua. */
    private const PREFIX_HP = ['0811', '0812', '0813', '0815', '0852', '0857', '0858', '0819', '0895', '0896'];

    private const PEKERJAAN = [
        'Wiraswasta', 'Petani', 'Pedagang', 'Guru', 'Karyawan Swasta', 'PNS',
        'Buruh', 'Nelayan', 'Tukang Bangunan', 'Driver Ojek', 'Penjahit', 'Karyawan Swasta',
    ];

    public function run(): void
    {
        $tahun = $this->tahunAjaranAktif();

        $mapel = $this->buatMataPelajaran();
        $guru = $this->buatGuru();
        $rombel = $this->buatRombel($tahun, $guru);
        $this->buatPengampu($rombel, $mapel, $guru);
        $this->buatSiswa($tahun, $rombel);

        $this->command?->info(sprintf(
            'Data master siap: %d mapel, %d guru, %d rombel, %d siswa.',
            count($mapel),
            count($guru),
            count($rombel),
            Siswa::count()
        ));
    }

    /**
     * Tahun ajaran aktif harus sudah ada — `PpdbSeeder` yang menandainya aktif,
     * dan ia dipanggil lebih dulu di `DatabaseSeeder`. Di sini hanya disimpan
     * supaya seeder ini aman dijalankan berulang.
     */
    private function tahunAjaranAktif(): TahunAjaran
    {
        return TahunAjaran::aktif()->firstOrFail();
    }

    /**
     * @return array<string, MataPelajaran> kunci = kode
     */
    private function buatMataPelajaran(): array
    {
        $hasil = [];

        foreach (self::MAPEL as $row) {
            $hasil[$row['kode']] = MataPelajaran::firstOrCreate(
                ['kode' => $row['kode']],
                [
                    'nama' => $row['nama'],
                    'kelompok' => $row['kelompok'],
                    'kkm' => $row['kkm'],
                    'is_aktif' => true,
                ]
            );
        }

        // Kode yang dibutuhkan seeder demo harus benar-benar ada; kalau ada
        // mapel yang dibuang dari daftar di atas, guard di sini yang menggigit
        // lebih awal daripada `firstOrFail()` yang jauh di bawah.
        foreach (self::MAPEL_WAJIB as $kode) {
            if (! isset($hasil[$kode])) {
                throw new \RuntimeException("DataMasterRealSeeder: mapel wajib {$kode} tidak ada di daftar MAPEL.");
            }
        }

        return $hasil;
    }

    /**
     * @return array<string, Guru> kunci = kode (bagian username)
     */
    private function buatGuru(): array
    {
        $hasil = [];

        foreach (self::GURU as $row) {
            $user = User::firstOrCreate(
                ['username' => 'guru.'.$row['kode']],
                [
                    'name' => $row['nama'],
                    'email' => $row['kode'].'@guru.example.com',
                    'password' => 'password',
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('guru');

            $hasil[$row['kode']] = Guru::firstOrCreate(
                ['nip' => $row['nip']],
                [
                    'user_id' => $user->id,
                    'nama' => $row['nama'],
                    'jenis_kelamin' => $row['gender'],
                    'telp' => $row['telepon'],
                    'alamat' => 'Ngaglik, Sleman, Yogyakarta',
                    'is_aktif' => true,
                ]
            );
        }

        return $hasil;
    }

    /**
     * Rombel per kelas aktif. Guru pertama menjadi wali kelas 7A, dst — satu
     * guru tidak mengampu dua rombel dalam tahun yang sama.
     *
     * @param  array<string, Guru>  $guru
     * @return array<string, Rombel> kunci = nama kelas
     */
    private function buatRombel(TahunAjaran $tahun, array $guru): array
    {
        $daftarGuru = array_values($guru);
        $hasil = [];
        $i = 0;

        foreach (self::KELAS as $namaKelas => $tingkat) {
            $kelas = Kelas::firstOrCreate(['nama_kelas' => $namaKelas], ['tingkat' => $tingkat]);

            $hasil[$namaKelas] = Rombel::firstOrCreate(
                ['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun->id],
                ['wali_guru_id' => $daftarGuru[$i % count($daftarGuru)]->id]
            );

            $i++;
        }

        return $hasil;
    }

    /**
     * Setiap rombel dapat MTK, BIN, BIG, IPA. Wali mengampu MTK di kelasnya
     * sendiri — kombinasi yang paling sering terjadi di sekolah — sedangkan
     * mapel lain dibagi ke guru yang tersedia.
     *
     * @param  array<string, Rombel>  $rombel
     * @param  array<string, MataPelajaran>  $mapel
     * @param  array<string, Guru>  $guru
     */
    private function buatPengampu(array $rombel, array $mapel, array $guru): void
    {
        $daftarGuru = array_values($guru);

        // Diambil per kode, bukan `array_filter` atas isi array. `array_filter`
        // mengirim VALUE — di sini objek MataPelajaran — ke callback, bukan
        // key-nya, sehingga pencocokan kode tidak pernah terjadi.
        $mapelDiajar = [];
        foreach (self::MAPEL_WAJIB as $kode) {
            $mapelDiajar[$kode] = $mapel[$kode];
        }

        $rotasi = 0;

        foreach ($rombel as $rombelModel) {
            $pertama = true;
            $sudahDipakai = [];

            foreach ($mapelDiajar as $mapelModel) {
                // Wali mengajar mapel pertama di kelasnya sendiri, itu pola yang
                // paling sering dipakai sekolah.
                if ($pertama) {
                    $pengampu = $rombelModel->wali_guru_id;
                } else {
                    // Guru berikutnya digilir, tapi satu guru
                    // tidak boleh dua mapel di rombel yang sama — itu kelihatan
                    // salah di layar Penugasan dan membuat beban ngajar guru itu
                    // menumpuk tanpa perlu.
                    do {
                        $pengampu = $daftarGuru[$rotasi++ % count($daftarGuru)]->id;
                    } while (in_array($pengampu, $sudahDipakai, true));
                }

                Pengampu::firstOrCreate(
                    ['mata_pelajaran_id' => $mapelModel->id, 'rombel_id' => $rombelModel->id],
                    ['guru_id' => $pengampu]
                );

                $sudahDipakai[] = $pengampu;
                $pertama = false;
            }
        }
    }

    /**
     * 10 siswa per kelas, 9 kelas = 90 siswa.
     *
     * Username = NISN dan password awal = NISN, mengikuti
     * `DemoSiswaSeeder`. `SiswaObserver` membuat akun orang-tua sendiri dari
     * `no_hp_orang_tua`, jadi nomor HP yang diisi di sini sekaligus membuat
     * akun wali yang bisa dipakai menguji area orang-tua.
     *
     * @param  array<string, Rombel>  $rombel
     */
    private function buatSiswa(TahunAjaran $tahun, array $rombel): void
    {
        $urut = 0;

        foreach ($rombel as $namaKelas => $rombelModel) {
            for ($i = 1; $i <= 10; $i++) {
                $urut++;
                $nama = $this->namaSiswa($urut);
                $nisn = '0088'.sprintf('%06d', $urut);
                $nis = '25'.sprintf('%05d', $urut);

                $user = User::firstOrCreate(
                    ['username' => $nisn],
                    [
                        'name' => $nama,
                        'email' => $nisn.'@siswa.example.com',
                        'password' => $nisn,
                        'email_verified_at' => now(),
                    ]
                );
                $user->assignRole('siswa');

                $siswa = Siswa::firstOrCreate(
                    ['nisn' => $nisn],
                    [
                        'user_id' => $user->id,
                        'nis' => $nis,
                        'tahun_ajaran_id' => $tahun->id,
                        'kelas_id' => $rombelModel->kelas_id,
                        'tanggal_diterima' => '2025-07-14',
                        'is_aktif' => true,
                        'nama_ayah' => $this->namaLaki($urut).' '.$this->namaBelakang($urut * 2),
                        'pekerjaan_ayah' => self::PEKERJAAN[$urut % count(self::PEKERJAAN)],
                        'nama_ibu' => $this->namaPerempuan($urut).' '.$this->namaBelakang($urut * 2 + 1),
                        'pekerjaan_ibu' => self::PEKERJAAN[($urut + 5) % count(self::PEKERJAAN)],
                        'no_hp_orang_tua' => $this->nomorHp($urut),
                    ]
                );

                RiwayatKelas::firstOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'kelas_id' => $rombelModel->kelas_id,
                        'tahun_ajaran_id' => $tahun->id,
                    ],
                    ['status' => 'aktif']
                );
            }
        }
    }

    /**
     * Nama depan dan belakang digabung dari indeks, bukan acak, supaya
     * `db:seed` kedua menghasilkan data yang sama persis.
     *
     * Penghitungan indeks: nama belakang memakai `($urut - 1) % 45`, nama depan
     * memakai `intdiv($urut, 2) % 45`. Karena gender berganti tiap siswa,
     * kombinasi (nama depan, nama belakang) tidak pernah sama untuk dua
     * siswa berbeda.
     */
    private function namaSiswa(int $urut): string
    {
        $pria = $urut % 2 === 0;

        return $pria
            ? $this->namaLaki($urut).' '.$this->namaBelakang($urut)
            : $this->namaPerempuan($urut).' '.$this->namaBelakang($urut);
    }

    private function namaLaki(int $urut): string
    {
        return self::NAMA_DEPAN_L[(intdiv($urut, 2)) % count(self::NAMA_DEPAN_L)];
    }

    private function namaPerempuan(int $urut): string
    {
        return self::NAMA_DEPAN_P[(intdiv($urut, 2)) % count(self::NAMA_DEPAN_P)];
    }

    private function namaBelakang(int $urut): string
    {
        return self::NAMA_BELAKANG[($urut - 1) % count(self::NAMA_BELAKANG)];
    }

    /**
     * Nomor HP format Indonesia: 4 digit prefiks operator + 8 digit. Unik per
     * siswa supaya akun orang-tua tidak saling berbagi.
     */
    private function nomorHp(int $urut): string
    {
        $prefiks = self::PREFIX_HP[$urut % count(self::PREFIX_HP)];

        return $prefiks.sprintf('%08d', 10000000 + $urut * 137);
    }
}
