<?php

namespace Database\Seeders;

use App\Models\ProfilSekolah;
use Illuminate\Database\Seeder;

class ProfilSekolahSeeder extends Seeder
{
    /**
     * Seed baris profil tunggal. Nilai bertanda GANTI adalah placeholder
     * yang wajib diganti admin via halaman Profil Sekolah.
     */
    public function run(): void
    {
        ProfilSekolah::firstOrCreate(
            [],
            [
                'nama_sekolah' => 'SMKN Ngaglik',
                'npsn' => null,
                'alamat' => 'GANTI: Jl. ... , Ngaglik, Sleman, Yogyakarta',
                'telp' => 'GANTI: (0274) ...',
                'email' => 'info@smknngaglik.sch.id',
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
