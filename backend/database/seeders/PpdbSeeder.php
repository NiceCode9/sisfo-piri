<?php

namespace Database\Seeders;

use App\Models\BiayaPendaftaran;
use App\Models\Gelombang;
use App\Models\JadwalPpdb;
use App\Models\JalurPendaftaran;
use App\Models\KuotaPendaftaran;
use App\Models\Pengumuman;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class PpdbSeeder extends Seeder
{
    /**
     * Jalur pendaftaran yang dirujuk by name, bukan by indeks.
     *
     * Versi lama memakai `pluck('id')[0..4]`, yang rapuh: begitu admin
     * menonaktifkan satu jalur, `where('aktif', true)` mengembalikan ID yang
     * berbeda dan kuota bisa menempel ke jalur yang salah.
     *
     * @var array<string, int>
     */
    private array $jalurId = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seeder ini memakai updateOrCreate, jadi counter `terisi` dan
        // `terisi_pendaftaran` dikembalikan ke angka seed. Itu memang
        // perilaku yang diharapkan untuk database demo, tapi berbahaya di
        // produksi: satu `db:seed` bisa mengembalikan counter penerimaan dan
        // pendaftaran yang sedang berjalan ke angka seed.
        if (app()->environment('production')) {
            return;
        }

        $this->seedTahunAjaran();
        $this->seedJalurPendaftaran();

        $tahunAjaranAktif = TahunAjaran::where('status_aktif', true)->first();

        if (! $tahunAjaranAktif) {
            $this->command?->error('Tidak ada tahun ajaran aktif. Seeder PPDB dihentikan.');

            return;
        }

        $this->seedKuota($tahunAjaranAktif);
        $this->seedBiaya($tahunAjaranAktif);
        $this->seedJadwal($tahunAjaranAktif);
        $this->seedPengumuman($tahunAjaranAktif);
        $this->seedGelombang($tahunAjaranAktif);

        $this->command?->info('Seeder PPDB selesai.');
    }

    /**
     * Tahun ajaran. Kunci: `nama_tahun_ajaran`.
     */
    private function seedTahunAjaran(): void
    {
        $tahunAjaran = [
            [
                'nama_tahun_ajaran' => '2026/2027',
                'tanggal_mulai' => '2025-07-01',
                'tanggal_selesai' => '2026-06-30',
                'status_aktif' => true,
            ],
            [
                'nama_tahun_ajaran' => '2025/2026',
                'tanggal_mulai' => '2024-07-01',
                'tanggal_selesai' => '2025-06-30',
                'status_aktif' => false,
            ],
            [
                'nama_tahun_ajaran' => '2024/2025',
                'tanggal_mulai' => '2023-07-01',
                'tanggal_selesai' => '2024-06-30',
                'status_aktif' => false,
            ],
            [
                'nama_tahun_ajaran' => '2023/2024',
                'tanggal_mulai' => '2023-07-01',
                'tanggal_selesai' => '2024-06-30',
                'status_aktif' => false,
            ],
        ];

        foreach ($tahunAjaran as $ta) {
            TahunAjaran::updateOrCreate(
                ['nama_tahun_ajaran' => $ta['nama_tahun_ajaran']],
                $ta
            );
        }
    }

    /**
     * Jalur pendaftaran. Kunci: `nama_jalur`.
     */
    private function seedJalurPendaftaran(): void
    {
        $jalurPendaftaran = [
            [
                'nama_jalur' => 'Jalur Reguler',
                'deskripsi' => 'Jalur pendaftaran untuk siswa umum dengan sistem seleksi berdasarkan nilai akademik',
                'aktif' => true,
            ],
            [
                'nama_jalur' => 'Jalur Prestasi',
                'deskripsi' => 'Jalur pendaftaran untuk siswa berprestasi akademik atau non-akademik',
                'aktif' => true,
            ],
            [
                'nama_jalur' => 'Jalur Afirmasi',
                'deskripsi' => 'Jalur pendaftaran untuk siswa dari keluarga kurang mampu',
                'aktif' => true,
            ],
            [
                'nama_jalur' => 'Jalur Mutasi',
                'deskripsi' => 'Jalur pendaftaran untuk siswa pindahan dari sekolah lain',
                'aktif' => true,
            ],
            [
                'nama_jalur' => 'Jalur Prestasi Olahraga',
                'deskripsi' => 'Jalur pendaftaran untuk siswa berprestasi di bidang olahraga (wajib upload sertifikat kejuaraan)',
                'aktif' => true,
                'wajib_sertifikat' => true,
            ],
        ];

        foreach ($jalurPendaftaran as $jalur) {
            $row = JalurPendaftaran::updateOrCreate(
                ['nama_jalur' => $jalur['nama_jalur']],
                $jalur
            );

            $this->jalurId[$row->nama_jalur] = $row->id;
        }
    }

    /**
     * Kuota pendaftaran. Kunci: (tahun_ajaran_id, jalur_pendaftaran_id).
     *
     * Kuota dipecah dua (lihat migrasi split_kuota_pendaftaran):
     *   - kuota_pendaftaran / terisi_pendaftaran = batas JUMLAH pendaftar.
     *     `null` = tidak dibatasi.
     *   - kuota / terisi = batas JUMLAH yang boleh DITERIMA.
     *
     * Skala disesuaikan dengan ukuran sekolah: 6 kelas berjalan dengan
     * ~37 siswa, sehingga kuota 240 kursi pendaftaran (190 diterima) realistis
     * untuk SMP. Angka lama (700 daftar / 320 terima) tidak masuk akal dan
     * membuat kartu kuota di landing page terlihat seperti sekolah raksasa.
     *
     * Label `keterangan` diturunkan dari nama tahun ajaran, bukan ditulis
     * manual — versi lama tertinggal "2024/2025" padahal barisnya menempel ke
     * 2026/2027.
     */
    private function seedKuota(TahunAjaran $tahun): void
    {
        $kuotaPendaftaran = [
            [
                'jalur' => 'Jalur Reguler',
                'kuota' => 100,
                'terisi' => 74,
                'kuota_pendaftaran' => 130,
                'terisi_pendaftaran' => 92,
            ],
            [
                'jalur' => 'Jalur Prestasi',
                'kuota' => 35,
                'terisi' => 24,
                'kuota_pendaftaran' => 45,
                'terisi_pendaftaran' => 31,
            ],
            [
                'jalur' => 'Jalur Afirmasi',
                'kuota' => 25,
                'terisi' => 17,
                'kuota_pendaftaran' => 35,
                'terisi_pendaftaran' => 21,
            ],
            [
                'jalur' => 'Jalur Mutasi',
                'kuota' => 15,
                'terisi' => 4,
                'kuota_pendaftaran' => 30,
                'terisi_pendaftaran' => 7,
            ],
            [
                'jalur' => 'Jalur Prestasi Olahraga',
                'kuota' => 15,
                'terisi' => 0,
                'kuota_pendaftaran' => null, // jalur kecil: biarkan tanpa batas
                // Sengaja 0. Jalur tanpa batas tidak ikut dijumlahkan di total
                // bounded (dipakai kartu kuota di landing page), jadi kalau
                // jalur ini punya pendaftar, total jalur dan total gelombang
                // akan berselisih dan dua kartu publik memberi angka berbeda.
                'terisi_pendaftaran' => 0,
            ],
        ];

        foreach ($kuotaPendaftaran as $kuota) {
            $jalur = $kuota['jalur'];

            unset($kuota['jalur']);

            KuotaPendaftaran::updateOrCreate(
                [
                    'tahun_ajaran_id' => $tahun->id,
                    'jalur_pendaftaran_id' => $this->jalurId[$jalur],
                ],
                $kuota + [
                    'tahun_ajaran_id' => $tahun->id,
                    'jalur_pendaftaran_id' => $this->jalurId[$jalur],
                    'keterangan' => "Kuota jalur {$jalur} tahun ajaran {$tahun->nama_tahun_ajaran}",
                ]
            );
        }
    }

    /**
     * Biaya pendaftaran. Kunci: (tahun_ajaran_id, jenis_biaya).
     */
    private function seedBiaya(TahunAjaran $tahun): void
    {
        $biayaPendaftaran = [
            [
                'jenis_biaya' => 'Biaya Pendaftaran',
                'jumlah' => 100000,
                'mata_uang' => 'IDR',
                'wajib_bayar' => true,
                'keterangan' => 'Biaya administrasi pendaftaran PPDB',
            ],
            [
                'jenis_biaya' => 'Uang Pangkal',
                'jumlah' => 2500000,
                'mata_uang' => 'IDR',
                'wajib_bayar' => true,
                'dapat_diangsur' => true,
                'keterangan' => 'Uang pangkal untuk siswa baru',
            ],
            [
                'jenis_biaya' => 'Seragam',
                'jumlah' => 500000,
                'mata_uang' => 'IDR',
                'wajib_bayar' => true,
                'keterangan' => 'Biaya pembelian seragam sekolah',
            ],
            [
                'jenis_biaya' => 'Buku Paket',
                'jumlah' => 750000,
                'mata_uang' => 'IDR',
                'wajib_bayar' => true,
                'keterangan' => 'Biaya pembelian buku paket pelajaran',
            ],
            [
                'jenis_biaya' => 'Ekstrakurikuler',
                'jumlah' => 200000,
                'mata_uang' => 'IDR',
                'wajib_bayar' => false,
                'keterangan' => 'Biaya kegiatan ekstrakurikuler (opsional)',
            ],
        ];

        foreach ($biayaPendaftaran as $biaya) {
            BiayaPendaftaran::updateOrCreate(
                [
                    'tahun_ajaran_id' => $tahun->id,
                    'jenis_biaya' => $biaya['jenis_biaya'],
                ],
                $biaya + ['tahun_ajaran_id' => $tahun->id]
            );
        }
    }

    /**
     * Jadwal PPDB. Kunci: (tahun_ajaran_id, tipe).
     *
     * Memakai `tipe` sebagai kunci, bukan `nama_jadwal`, karena gate pendaftaran
     * di `JadwalPpdb::jendelaPendaftaran()` mengambil satu baris bertipe
     * `pendaftaran`. Kunci ini menjamin tidak akan pernah ada dua baris
     * pendaftaran untuk satu tahun ajaran.
     *
     * Tanggal dibuat relatif terhadap hari ini supaya data hasil seed selalu
     * bisa langsung dicoba: fase pendaftaran sedang berlangsung, fase
     * berikutnya menyusul. Tanggal statis membuat gate pendaftaran menutup
     * form begitu tanggalnya lewat.
     */
    private function seedJadwal(TahunAjaran $tahun): void
    {
        $hariIni = now()->startOfDay();

        $jadwalPpdb = [
            [
                'nama_jadwal' => 'Pendaftaran Online',
                'tipe' => 'pendaftaran',
                'tanggal_mulai' => $hariIni->copy()->subMonth()->toDateString(),
                'tanggal_selesai' => $hariIni->copy()->addMonths(2)->toDateString(),
                'keterangan' => 'Periode pendaftaran online untuk calon siswa baru',
            ],
            [
                'nama_jadwal' => 'Verifikasi Berkas',
                'tipe' => 'verifikasi',
                'tanggal_mulai' => $hariIni->copy()->addMonths(2)->addDay()->toDateString(),
                'tanggal_selesai' => $hariIni->copy()->addMonths(2)->addDays(10)->toDateString(),
                'keterangan' => 'Periode verifikasi berkas pendaftaran',
            ],
            [
                'nama_jadwal' => 'Tes Seleksi',
                'tipe' => 'tes',
                'tanggal_mulai' => $hariIni->copy()->addMonths(2)->addDays(15)->toDateString(),
                'tanggal_selesai' => $hariIni->copy()->addMonths(2)->addDays(20)->toDateString(),
                'keterangan' => 'Pelaksanaan tes seleksi untuk calon siswa',
            ],
            [
                'nama_jadwal' => 'Pengumuman Hasil',
                'tipe' => 'pengumuman',
                'tanggal_mulai' => $hariIni->copy()->addMonths(2)->addDays(25)->toDateString(),
                'tanggal_selesai' => $hariIni->copy()->addMonths(2)->addDays(25)->toDateString(),
                'keterangan' => 'Pengumuman hasil seleksi PPDB',
            ],
            [
                'nama_jadwal' => 'Daftar Ulang',
                'tipe' => 'daftar_ulang',
                'tanggal_mulai' => $hariIni->copy()->addMonths(2)->addDays(26)->toDateString(),
                'tanggal_selesai' => $hariIni->copy()->addMonths(3)->toDateString(),
                'keterangan' => 'Periode daftar ulang untuk siswa yang diterima',
            ],
        ];

        foreach ($jadwalPpdb as $jadwal) {
            JadwalPpdb::updateOrCreate(
                [
                    'tahun_ajaran_id' => $tahun->id,
                    'tipe' => $jadwal['tipe'],
                ],
                $jadwal + ['tahun_ajaran_id' => $tahun->id]
            );
        }
    }

    /**
     * Pengumuman. Kunci: (tahun_ajaran_id, judul).
     *
     * Tanggal pengumuman dibuat relatif terhadap hari ini, sama seperti jadwal.
     * Versi lama menulis tanggal statis (April/Juni 2026) sekaligus menjanjikan
     * "pendaftaran dibuka mulai 1 Mei 2026", padahal jadwal pendaftaran di-seed
     * berjalan dari satu bulan lalu sampai dua bulan depan - jadi isi
     * pengumuman bertentangan dengan data jadwal di database.
     */
    private function seedPengumuman(TahunAjaran $tahun): void
    {
        $hariIni = now()->startOfDay();
        $tahunAjaran = $tahun->nama_tahun_ajaran;

        $pengumumans = [
            [
                'judul' => "Jadwal PPDB {$tahunAjaran} Telah Dibuka",
                'isi' => "Pendaftaran online PPDB tahun ajaran {$tahunAjaran} sudah dibuka. Silakan daftar melalui menu Pendaftaran.",
                'tanggal_pengumuman' => $hariIni->copy()->subWeek()->toDateString(),
                'status_aktif' => true,
            ],
            [
                'judul' => "Informasi Biaya Pendidikan {$tahunAjaran}",
                'isi' => "Rincian biaya pendidikan tahun ajaran {$tahunAjaran} dapat dilihat pada halaman Biaya. Siswa berprestasi mendapat beasiswa.",
                'tanggal_pengumuman' => $hariIni->copy()->subWeek()->addDays(2)->toDateString(),
                'status_aktif' => true,
            ],
            [
                'judul' => 'Pengumuman Hasil Seleksi Gelombang 1',
                'isi' => 'Hasil seleksi gelombang 1 telah diumumkan. Calon yang dinyatakan diterima harap melakukan daftar ulang sesuai jadwal.',
                'tanggal_pengumuman' => $hariIni->copy()->addMonths(2)->addDays(25)->toDateString(),
                'status_aktif' => true,
            ],
        ];

        foreach ($pengumumans as $p) {
            Pengumuman::updateOrCreate(
                [
                    'tahun_ajaran_id' => $tahun->id,
                    'judul' => $p['judul'],
                ],
                $p + ['tahun_ajaran_id' => $tahun->id]
            );
        }
    }

    /**
     * Gelombang pendaftaran. Kunci: (tahun_ajaran_id, nomor_urut).
     *
     * Tabel `gelombangs` sudah punya unique constraint pada pasangan itu,
     * jadi `create()` versi lama membuat `db:seed` kedua gagal dengan
     * UniqueConstraintViolationException - dan karena tidak dibungkus transaksi,
     * langkah 1-6 sudah terlanjur ter-*commit* saat itu.
     *
     * Dua koreksi penting:
     *
     * 1. Tanggal dibuat RELATIF terhadap hari ini, sama seperti jadwal PPDB.
     *    Versi lama memakai tanggal statis (2026-10-01), padahal gate gelombang
     *    kini benar-benar ditegakkan: setelah 31 Nov 2026 semua gelombang
     *    tertutup permanen dan landing page tidak menampilkan apa pun.
     *
     * 2. Total kuota dan terisi sengaja sama dengan total kuota jalur
     *    (240 kursi, 151 terisi). Kedua sistem menghitung pendaftar yang sama
     *    dan keduanya sama-sama ditampilkan di halaman publik, jadi kalau
     *    angkanya berbeda admin mendapat dua jawaban berbeda untuk pertanyaan
     *    yang sama: "berapa kursi yang tersedia?". Jalur tanpa batas
     *    (`kuota_pendaftaran` NULL) ikut dihitung di `terisi` tapi tidak pernah
     *    dihitung di `kapasitas`, jadi pendaftar pada jalur itu harus 0 —
     *    kalau tidak, total gelombangan akan melebihi total jalur bounded.
     */
    private function seedGelombang(TahunAjaran $tahun): void
    {
        $hariIni = now()->startOfDay();

        $gelombangs = [
            [
                'nama_gelombang' => 'Gelombang 1',
                'nomor_urut' => 1,
                'badge' => 'EARLY BIRD',
                'buka' => $hariIni->copy()->subMonth(),
                'tutup' => $hariIni->copy()->addMonths(2),
                'kuota' => 100,
                'terisi' => 92,
                'diskon_persen' => 15,
                'keuntungan' => ['Gratis seragam olahraga', 'Prioritas pilihan kelas'],
                'keterangan' => 'Gelombang awal dengan diskon terbesar',
                'warna_border' => 'primary-600',
                'is_aktif' => true,
            ],
            [
                'nama_gelombang' => 'Gelombang 2',
                'nomor_urut' => 2,
                'badge' => 'RECOMMENDED',
                'buka' => $hariIni->copy()->addMonths(2)->addDays(15),
                'tutup' => $hariIni->copy()->addMonths(4),
                'kuota' => 85,
                'terisi' => 42,
                'diskon_persen' => 10,
                'keuntungan' => ['Gratis tas sekolah', 'Gratis try-out persiapan'],
                'keterangan' => 'Gelombang favorit',
                'warna_border' => 'secondary-600',
                'is_aktif' => true,
            ],
            [
                'nama_gelombang' => 'Gelombang 3',
                'nomor_urut' => 3,
                'badge' => 'LAST CHANCE',
                'buka' => $hariIni->copy()->addMonths(4)->addDays(15),
                'tutup' => $hariIni->copy()->addMonths(6),
                'kuota' => 55,
                'terisi' => 17,
                'diskon_persen' => 5,
                'keuntungan' => ['Gratis alat tulis', 'Kesempatan terakhir!'],
                'keterangan' => 'Gelombang terakhir',
                'warna_border' => 'accent-600',
                'is_aktif' => true,
            ],
        ];

        foreach ($gelombangs as $g) {
            $buka = $g['buka'];
            $tutup = $g['tutup'];

            Gelombang::updateOrCreate(
                [
                    'tahun_ajaran_id' => $tahun->id,
                    'nomor_urut' => $g['nomor_urut'],
                ],
                [
                    'tahun_ajaran_id' => $tahun->id,
                    'nama_gelombang' => $g['nama_gelombang'],
                    'nomor_urut' => $g['nomor_urut'],
                    'badge' => $g['badge'],
                    'tanggal_buka' => $buka->toDateString(),
                    'tanggal_tutup' => $tutup->toDateString(),
                    // Tes & pengumuman diletakkan di tengah-tengah jendela.
                    'tanggal_tes' => $buka->copy()->addDays(10)->toDateString(),
                    'tanggal_pengumuman' => $buka->copy()->addDays(20)->toDateString(),
                    'kuota' => $g['kuota'],
                    'terisi' => $g['terisi'],
                    'diskon_persen' => $g['diskon_persen'],
                    'keuntungan' => $g['keuntungan'],
                    'keterangan' => $g['keterangan'],
                    'warna_border' => $g['warna_border'],
                    'is_aktif' => $g['is_aktif'],
                ]
            );
        }
    }
}
