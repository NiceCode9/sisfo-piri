<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\WaliKelas;
use Illuminate\Database\Seeder;

class AkademikSeeder extends Seeder
{
    /**
     * Data uji kasus sekolah: Guru A mengajar MTK di 7A+7B,
     * Guru D mengajar MTK di 7C+7D (tahun ajaran aktif).
     */
    public function run(): void
    {
        $tahun = TahunAjaran::aktif()->firstOrFail();

        foreach ([
            ['kode' => 'MTK', 'nama' => 'Matematika', 'kelompok' => 'A', 'kkm' => 75],
            ['kode' => 'IPA', 'nama' => 'Ilmu Pengetahuan Alam', 'kelompok' => 'A', 'kkm' => 75],
            ['kode' => 'BIN', 'nama' => 'Bahasa Indonesia', 'kelompok' => 'A', 'kkm' => 75],
            ['kode' => 'BIG', 'nama' => 'Bahasa Inggris', 'kelompok' => 'A', 'kkm' => 75],
        ] as $mapel) {
            MataPelajaran::firstOrCreate(['kode' => $mapel['kode']], $mapel);
        }

        foreach (['7A', '7B', '7C', '7D'] as $nama) {
            Kelas::firstOrCreate(['nama_kelas' => $nama], ['tingkat' => '7']);
        }

        $guruA = $this->buatGuru('196501011990031001', 'Guru A', 'L', 'guru-a');
        $guruD = $this->buatGuru('196802021995122002', 'Guru D', 'P', 'guru-d');

        $mtk = MataPelajaran::where('kode', 'MTK')->firstOrFail();
        $kelas = Kelas::whereIn('nama_kelas', ['7A', '7B', '7C', '7D'])->pluck('id', 'nama_kelas');

        // Guru A: MTK 7A + 7B. Guru D: MTK 7C + 7D.
        foreach (['7A' => $guruA, '7B' => $guruA, '7C' => $guruD, '7D' => $guruD] as $namaKelas => $guru) {
            Pengampu::firstOrCreate([
                'guru_id' => $guru->id,
                'mata_pelajaran_id' => $mtk->id,
                'kelas_id' => $kelas[$namaKelas],
                'tahun_ajaran_id' => $tahun->id,
            ]);
        }

        WaliKelas::firstOrCreate([
            'kelas_id' => $kelas['7A'],
            'tahun_ajaran_id' => $tahun->id,
        ], ['guru_id' => $guruA->id]);

        WaliKelas::firstOrCreate([
            'kelas_id' => $kelas['7C'],
            'tahun_ajaran_id' => $tahun->id,
        ], ['guru_id' => $guruD->id]);
    }

    protected function buatGuru(string $nip, string $nama, string $jk, string $username): Guru
    {
        $user = User::firstOrCreate(
            ['username' => $username],
            ['name' => $nama, 'email' => $username.'@example.com', 'password' => 'password'],
        );
        $user->assignRole('guru');

        return Guru::firstOrCreate(
            ['nip' => $nip],
            ['user_id' => $user->id, 'nama' => $nama, 'jenis_kelamin' => $jk, 'is_aktif' => true],
        );
    }
}
