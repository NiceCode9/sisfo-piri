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
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seeder Tahun Ajaran
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
                'tanggal_mulai' => '2024-07-01',
                'tanggal_selesai' => '2025-06-30',
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
            TahunAjaran::create($ta);
        }

        // 2. Seeder Jalur Pendaftaran
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
            JalurPendaftaran::create($jalur);
        }

        // 3. Seeder Kuota Pendaftaran
        $tahunAjaranAktif = TahunAjaran::where('status_aktif', true)->first();
        $jalurIds = JalurPendaftaran::where('aktif', true)->pluck('id')->toArray();

        // Kuota dipecah dua (lihat migrasi split_kuota_pendaftaran):
        //   - kuota_pendaftaran / terisi_pendaftaran = batas JUMLAH pendaftar.
        //     Dilewati `null` = tidak dibatasi.
        //   - kuota / terisi = batas JUMLAH yang boleh DITERIMA.
        $kuotaPendaftaran = [
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'jalur_pendaftaran_id' => $jalurIds[0], // Jalur Reguler
                'kuota' => 200,
                'terisi' => 150,
                'kuota_pendaftaran' => 400,
                'terisi_pendaftaran' => 320,
                'keterangan' => 'Kuota untuk jalur reguler tahun ajaran 2024/2025',
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'jalur_pendaftaran_id' => $jalurIds[1], // Jalur Prestasi
                'kuota' => 50,
                'terisi' => 35,
                'kuota_pendaftaran' => 150,
                'terisi_pendaftaran' => 98,
                'keterangan' => 'Kuota untuk jalur prestasi tahun ajaran 2024/2025',
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'jalur_pendaftaran_id' => $jalurIds[2], // Jalur Afirmasi
                'kuota' => 30,
                'terisi' => 20,
                'kuota_pendaftaran' => 90,
                'terisi_pendaftaran' => 55,
                'keterangan' => 'Kuota untuk jalur afirmasi tahun ajaran 2024/2025',
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'jalur_pendaftaran_id' => $jalurIds[3], // Jalur Mutasi
                'kuota' => 20,
                'terisi' => 5,
                'kuota_pendaftaran' => 60,
                'terisi_pendaftaran' => 18,
                'keterangan' => 'Kuota untuk jalur mutasi tahun ajaran 2024/2025',
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'jalur_pendaftaran_id' => $jalurIds[4], // Jalur Prestasi Olahraga
                'kuota' => 20,
                'terisi' => 0,
                'kuota_pendaftaran' => null, // jalur kecil: biarkan tanpa batas
                'terisi_pendaftaran' => 0,
                'keterangan' => 'Kuota untuk jalur prestasi olahraga tahun ajaran 2024/2025',
            ],
        ];

        foreach ($kuotaPendaftaran as $kuota) {
            KuotaPendaftaran::create($kuota);
        }

        // 4. Seeder Biaya Pendaftaran
        $biayaPendaftaran = [
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'jenis_biaya' => 'Biaya Pendaftaran',
                'jumlah' => 100000,
                'mata_uang' => 'IDR',
                'wajib_bayar' => true,
                'keterangan' => 'Biaya administrasi pendaftaran PPDB',
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'jenis_biaya' => 'Uang Pangkal',
                'jumlah' => 2500000,
                'mata_uang' => 'IDR',
                'wajib_bayar' => true,
                'dapat_diangsur' => true,
                'keterangan' => 'Uang pangkal untuk siswa baru',
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'jenis_biaya' => 'Seragam',
                'jumlah' => 500000,
                'mata_uang' => 'IDR',
                'wajib_bayar' => true,
                'keterangan' => 'Biaya pembelian seragam sekolah',
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'jenis_biaya' => 'Buku Paket',
                'jumlah' => 750000,
                'mata_uang' => 'IDR',
                'wajib_bayar' => true,
                'keterangan' => 'Biaya pembelian buku paket pelajaran',
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'jenis_biaya' => 'Ekstrakurikuler',
                'jumlah' => 200000,
                'mata_uang' => 'IDR',
                'wajib_bayar' => false,
                'keterangan' => 'Biaya kegiatan ekstrakurikuler (opsional)',
            ],
        ];

        foreach ($biayaPendaftaran as $biaya) {
            BiayaPendaftaran::create($biaya);
        }

        // 5. Seeder Jadwal PPDB
        //
        // Tanggal dibuat relatif terhadap hari ini supaya data hasil seed selalu
        // bisa langsung dicoba: fase pendaftaran sedang berlangsung, fase
        // berikutnya menyusul. Tanggal statis membuat gate pendaftaran (tipe
        // `pendaftaran`) menutup form begitu tanggalnya lewat.
        $hariIni = now()->startOfDay();

        $jadwalPpdb = [
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'nama_jadwal' => 'Pendaftaran Online',
                'tipe' => 'pendaftaran',
                'tanggal_mulai' => $hariIni->copy()->subMonth()->toDateString(),
                'tanggal_selesai' => $hariIni->copy()->addMonths(2)->toDateString(),
                'keterangan' => 'Periode pendaftaran online untuk calon siswa baru',
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'nama_jadwal' => 'Verifikasi Berkas',
                'tipe' => 'verifikasi',
                'tanggal_mulai' => $hariIni->copy()->addMonths(2)->addDay()->toDateString(),
                'tanggal_selesai' => $hariIni->copy()->addMonths(2)->addDays(10)->toDateString(),
                'keterangan' => 'Periode verifikasi berkas pendaftaran',
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'nama_jadwal' => 'Tes Seleksi',
                'tipe' => 'tes',
                'tanggal_mulai' => $hariIni->copy()->addMonths(2)->addDays(15)->toDateString(),
                'tanggal_selesai' => $hariIni->copy()->addMonths(2)->addDays(20)->toDateString(),
                'keterangan' => 'Pelaksanaan tes seleksi untuk calon siswa',
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'nama_jadwal' => 'Pengumuman Hasil',
                'tipe' => 'pengumuman',
                'tanggal_mulai' => $hariIni->copy()->addMonths(2)->addDays(25)->toDateString(),
                'tanggal_selesai' => $hariIni->copy()->addMonths(2)->addDays(25)->toDateString(),
                'keterangan' => 'Pengumuman hasil seleksi PPDB',
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'nama_jadwal' => 'Daftar Ulang',
                'tipe' => 'daftar_ulang',
                'tanggal_mulai' => $hariIni->copy()->addMonths(2)->addDays(26)->toDateString(),
                'tanggal_selesai' => $hariIni->copy()->addMonths(3)->toDateString(),
                'keterangan' => 'Periode daftar ulang untuk siswa yang diterima',
            ],
        ];

        foreach ($jadwalPpdb as $jadwal) {
            JadwalPpdb::create($jadwal);
        }

        // 6. Seeder Pengumuman
        $pengumumans = [
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'judul' => 'Jadwal PPDB 2026/2027 Telah Dibuka',
                'isi' => 'Pendaftaran online PPDB tahun ajaran 2026/2027 resmi dibuka mulai 1 Mei 2026. Silakan daftar melalui menu Pendaftaran.',
                'tanggal_pengumuman' => '2026-04-28',
                'status_aktif' => true,
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'judul' => 'Pengumuman Hasil Seleksi Gelombang 1',
                'isi' => 'Hasil seleksi gelombang 1 telah diumumkan. Calon yang dinyatakan diterima harap melakukan daftar ulang sesuai jadwal.',
                'tanggal_pengumuman' => '2026-06-25',
                'status_aktif' => true,
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'judul' => 'Informasi Biaya Pendidikan 2026/2027',
                'isi' => 'Rincian biaya pendidikan tahun ajaran 2026/2027 dapat dilihat pada halaman Biaya. Siswa berprestasi berkesempatan mendapat beasiswa.',
                'tanggal_pengumuman' => '2026-04-30',
                'status_aktif' => true,
            ],
        ];

        foreach ($pengumumans as $p) {
            Pengumuman::create($p);
        }

        // 7. Seeder Gelombang (terpisah dari jadwal_ppdbs)
        $gelombangs = [
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'nama_gelombang' => 'Gelombang 1',
                'nomor_urut' => 1,
                'badge' => 'EARLY BIRD',
                'tanggal_buka' => '2026-10-01',
                'tanggal_tutup' => '2026-11-30',
                'tanggal_tes' => '2026-12-07',
                'tanggal_pengumuman' => '2026-12-14',
                'kuota' => 80,
                'terisi' => 28,
                'diskon_persen' => 15,
                'keuntungan' => ['Gratis seragam olahraga', 'Prioritas pilihan kelas'],
                'keterangan' => 'Gelombang awal dengan diskon terbesar',
                'warna_border' => 'primary-600',
                'is_aktif' => true,
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'nama_gelombang' => 'Gelombang 2',
                'nomor_urut' => 2,
                'badge' => 'RECOMMENDED',
                'tanggal_buka' => '2026-12-15',
                'tanggal_tutup' => '2027-01-31',
                'tanggal_tes' => '2027-02-08',
                'tanggal_pengumuman' => '2027-02-15',
                'kuota' => 70,
                'terisi' => 14,
                'diskon_persen' => 10,
                'keuntungan' => ['Gratis tas sekolah', 'Gratis try-out persiapan'],
                'keterangan' => 'Gelombang favorit',
                'warna_border' => 'secondary-600',
                'is_aktif' => true,
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranAktif->id,
                'nama_gelombang' => 'Gelombang 3',
                'nomor_urut' => 3,
                'badge' => 'LAST CHANCE',
                'tanggal_buka' => '2027-02-16',
                'tanggal_tutup' => '2027-03-31',
                'tanggal_tes' => '2027-04-05',
                'tanggal_pengumuman' => '2027-04-12',
                'kuota' => 30,
                'terisi' => 3,
                'diskon_persen' => 5,
                'keuntungan' => ['Gratis alat tulis', 'Kesempatan terakhir!'],
                'keterangan' => 'Gelombang terakhir',
                'warna_border' => 'accent-600',
                'is_aktif' => true,
            ],
        ];

        foreach ($gelombangs as $g) {
            Gelombang::create($g);
        }

        echo "Seeder PPDB berhasil dijalankan!\n";
        echo "Data yang dibuat:\n";
    }
}
