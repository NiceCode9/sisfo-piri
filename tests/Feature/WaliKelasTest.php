<?php

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\WaliKelas;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
});

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

/*
 * ARSIP read-only pasca-cutover rombel: route CRUD wali-kelas dicabut,
 * wali kini tercatat di rombels.wali_guru_id. File ini mengunci keputusan:
 * route lama 404 dan model arsip tetap terbaca.
 */

test('route wali-kelas sudah dicabut (cutover rombel)', function () {
    $this->actingAs(superAdmin())->get('/admin/wali-kelas')->assertNotFound();
});

test('model arsip wali kelas tetap terbaca', function () {
    $user = User::factory()->create(['username' => 'guru-wali']);
    $guru = Guru::create(['user_id' => $user->id, 'nama' => 'Guru Wali', 'jenis_kelamin' => 'L', 'is_aktif' => true]);
    $kelas = Kelas::create(['nama_kelas' => '7A', 'tingkat' => '7']);
    $tahun = TahunAjaran::aktif()->first();

    $wali = WaliKelas::create([
        'guru_id' => $guru->id,
        'kelas_id' => $kelas->id,
        'tahun_ajaran_id' => $tahun->id,
    ]);

    expect(WaliKelas::find($wali->id))->not->toBeNull()
        ->and($wali->guru->nama)->toBe('Guru Wali');
});
