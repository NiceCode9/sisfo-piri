<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoKenaikanSeeder extends Seeder
{
    /**
     * 32 siswa trial kenaikan (8 per kelas: 7A, 7B, 8A, 9A) beserta
     * kelas lanjutan (8A, 8B, 9A), tahun depan non-aktif, dan akun login.
     * Username + password awal = NISN. Idempoten via NISN.
     */
    public function run(): void
    {
        $tahunAktif = TahunAjaran::aktif()->firstOrFail();

        TahunAjaran::firstOrCreate(
            ['nama_tahun_ajaran' => '2027/2028'],
            ['tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'status_aktif' => false],
        );

        $kelas8A = Kelas::firstOrCreate(['nama_kelas' => '8A'], ['tingkat' => '8']);
        Kelas::firstOrCreate(['nama_kelas' => '8B'], ['tingkat' => '8']);
        $kelas9A = Kelas::firstOrCreate(['nama_kelas' => '9A'], ['tingkat' => '9']);

        $asal = [
            '7A' => Kelas::where('nama_kelas', '7A')->firstOrFail()->id,
            '7B' => Kelas::where('nama_kelas', '7B')->firstOrFail()->id,
            '8A' => $kelas8A->id,
            '9A' => $kelas9A->id,
        ];

        // 7A→8A, 7B→8B, 8A→9A, 9A→LULUS: 8 siswa per kelas asal.
        $n = 0;

        foreach (['7A', '7B', '8A', '9A'] as $namaKelas) {
            for ($i = 1; $i <= 8; $i++) {
                $n++;
                $nis = sprintf('31%02d', $n);
                $nisn = sprintf('008002%04d', $n);
                $nama = "Siswa Kenaikan {$namaKelas}-{$i}";

                $user = User::firstOrCreate(
                    ['username' => $nisn],
                    ['name' => $nama, 'email' => "siswa-kenaikan-{$n}@example.com", 'password' => $nisn],
                );
                $user->assignRole('siswa');

                $siswa = Siswa::firstOrCreate(
                    ['nisn' => $nisn],
                    [
                        'user_id' => $user->id,
                        'nis' => $nis,
                        'tahun_ajaran_id' => $tahunAktif->id,
                        'kelas_id' => $asal[$namaKelas],
                        'tanggal_diterima' => now()->toDateString(),
                        'is_aktif' => true,
                        'nama_ayah' => "Ayah Kenaikan {$n}",
                        'pekerjaan_ayah' => 'Wiraswasta',
                        'nama_ibu' => "Ibu Kenaikan {$n}",
                        'pekerjaan_ibu' => 'IRT',
                        'no_hp_orang_tua' => '0812345600'.sprintf('%02d', $n),
                    ],
                );

                RiwayatKelas::firstOrCreate(
                    ['siswa_id' => $siswa->id, 'kelas_id' => $asal[$namaKelas], 'tahun_ajaran_id' => $tahunAktif->id],
                    ['status' => 'aktif'],
                );
            }
        }
    }
}
