<?php

namespace Database\Seeders;

use App\Models\ProfilSekolah;
use Illuminate\Database\Seeder;

class ProfilSekolahSeeder extends Seeder
{
    /**
     * Nama resmi sekolah.
     *
     * Landing page menyebut syarat "lulusan kelas 6 SD/MI", rapor SD, dan usia
     * maksimal 15 tahun - itu syarat sekolah jenjang SMP, jadi nama sekolahnya
     * yang harus mengikuti, bukan teks persyaratannya.
     */
    public const NAMA_SEKOLAH = 'SMP PIRI NGAGLIK';

    /**
     * Seed baris profil tunggal. Nilai bertanda GANTI adalah placeholder
     * yang wajib diganti admin via halaman Profil Sekolah, dan sengaja
     * tidak ditampilkan di halaman publik (lihat accessor `*Bersih`).
     */
    public function run(): void
    {
        ProfilSekolah::firstOrCreate(
            [],
            [
                'nama_sekolah' => self::NAMA_SEKOLAH,
                'npsn' => null,
                'alamat' => 'GANTI: Jl. ... , Ngaglik, Sleman, Yogyakarta',
                'telp' => 'GANTI: (0274) ...',
                // Dikosongkan, bukan dikarang: domain sekolah tidak ada di
                // repo dan menebak domain bisa mengirim email ke pihak lain.
                'email' => null,
                'website' => null,
                'tahun_berdiri' => null,
                'akreditasi' => null,
                'sambutan' => 'GANTI: sambutan kepala sekolah untuk halaman tentang kami.',
                'visi' => 'GANTI: visi sekolah.',
                'misi' => [],
                'nama_kepala' => 'GANTI: Nama Kepala Sekolah',
            ]
        );
    }
}
