<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSiswaSeeder extends Seeder
{
    /**
     * 5 siswa trial murni manual (tanpa calon PPDB) beserta akun login.
     * Username + password awal = NISN. Idempoten via NISN.
     */
    public function run(): void
    {
        $tahun = TahunAjaran::aktif()->firstOrFail();

        $data = [
            ['nis' => '2001', 'nisn' => '0080011001', 'nama' => 'Siswa Demo Satu', 'kelas' => '7A', 'aktif' => true],
            ['nis' => '2002', 'nisn' => '0080011002', 'nama' => 'Siswa Demo Dua', 'kelas' => '7A', 'aktif' => true],
            ['nis' => '2003', 'nisn' => '0080011003', 'nama' => 'Siswa Demo Tiga', 'kelas' => '7B', 'aktif' => true],
            ['nis' => '2004', 'nisn' => '0080011004', 'nama' => 'Siswa Demo Empat', 'kelas' => '7B', 'aktif' => true],
            ['nis' => '2005', 'nisn' => '0080011005', 'nama' => 'Siswa Demo Lima', 'kelas' => '7C', 'aktif' => false],
        ];

        foreach ($data as $i => $row) {
            $n = $i + 1;

            $user = User::firstOrCreate(
                ['username' => $row['nisn']],
                ['name' => $row['nama'], 'email' => 'siswa-demo'.$n.'@example.com', 'password' => $row['nisn']],
            );
            $user->assignRole('siswa');

            $kelas = Kelas::where('nama_kelas', $row['kelas'])->firstOrFail();

            $siswa = Siswa::firstOrCreate(
                ['nisn' => $row['nisn']],
                [
                    'user_id' => $user->id,
                    'nis' => $row['nis'],
                    'tahun_ajaran_id' => $tahun->id,
                    'kelas_id' => $kelas->id,
                    'tanggal_diterima' => now()->toDateString(),
                    'is_aktif' => $row['aktif'],
                    'nama_ayah' => 'Ayah Demo '.$n,
                    'pekerjaan_ayah' => 'Wiraswasta',
                    'nama_ibu' => 'Ibu Demo '.$n,
                    'pekerjaan_ibu' => 'IRT',
                    'no_hp_orang_tua' => '0812345600'.sprintf('%02d', $n),
                ],
            );

            RiwayatKelas::firstOrCreate(
                ['siswa_id' => $siswa->id, 'kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun->id],
                ['status' => 'aktif'],
            );
        }
    }
}
