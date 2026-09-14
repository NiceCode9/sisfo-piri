<?php

use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\Rombel;
use App\Models\Tugas;
use App\Models\User;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
});

test('rekap menampilkan matriks nilai', function () {
    $rombel = Rombel::firstOrFail();
    $mapel = MataPelajaran::aktif()->firstOrFail();
    $tugas = Tugas::create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id, 'judul' => 'Tugas Rekap', 'is_aktif' => true]);
    $user = User::factory()->create(['username' => 'siswa-rekap']);
    $user->assignRole('siswa');
    $siswa = \App\Models\Siswa::create(['user_id' => $user->id, 'nis' => '9101', 'nisn' => '008099101', 'tahun_ajaran_id' => $rombel->tahun_ajaran_id, 'kelas_id' => $rombel->kelas_id, 'is_aktif' => true]);
    \App\Models\RiwayatKelas::create(['siswa_id' => $siswa->id, 'kelas_id' => $rombel->kelas_id, 'tahun_ajaran_id' => $rombel->tahun_ajaran_id, 'status' => 'aktif']);
    PengumpulanTugas::create(['tugas_id' => $tugas->id, 'siswa_id' => $siswa->id, 'nilai' => 80]);

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin)->get(route('admin.tugas.rekap', ['rombel_id' => $rombel->id]))
        ->assertOk()->assertSee('80')->assertSee('Rekap Nilai');
});

test('export excel/pdf rekap', function () {
    $rombel = Rombel::firstOrFail();
    $mapel = MataPelajaran::aktif()->firstOrFail();
    $tugas = Tugas::create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id, 'judul' => 'Tugas Export', 'is_aktif' => true]);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin)->get(route('admin.tugas.rekap.excel', ['rombel_id' => $rombel->id]))
        ->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $this->actingAs($admin)->get(route('admin.tugas.rekap.pdf', ['rombel_id' => $rombel->id]))
        ->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('siswa dapat melihat rekap sendiri', function () {
    $rombel = Rombel::firstOrFail();
    $mapel = MataPelajaran::aktif()->firstOrFail();
    $tugas = Tugas::create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id, 'judul' => 'Tugas Siswa Rekap', 'is_aktif' => true]);
    $user = User::factory()->create(['username' => 'siswa-rekap2']);
    $user->assignRole('siswa');
    $siswa = \App\Models\Siswa::create(['user_id' => $user->id, 'nis' => '9102', 'nisn' => '008099102', 'tahun_ajaran_id' => $rombel->tahun_ajaran_id, 'kelas_id' => $rombel->kelas_id, 'is_aktif' => true]);
    \App\Models\RiwayatKelas::create(['siswa_id' => $siswa->id, 'kelas_id' => $rombel->kelas_id, 'tahun_ajaran_id' => $rombel->tahun_ajaran_id, 'status' => 'aktif']);
    PengumpulanTugas::create(['tugas_id' => $tugas->id, 'siswa_id' => $siswa->id, 'nilai' => 90]);

    $this->actingAs($user)->get(route('siswa.tugas.rekap'))
        ->assertOk()->assertSee('90');
});
