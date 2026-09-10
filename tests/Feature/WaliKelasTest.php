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

if (! function_exists('buatWaliDasar')) {
    function buatWaliDasar(): array
    {
        $user = User::factory()->create(['username' => 'guru-wali']);
        $guru = Guru::create(['user_id' => $user->id, 'nama' => 'Guru Wali', 'jenis_kelamin' => 'L', 'is_aktif' => true]);
        $kelas = Kelas::create(['nama_kelas' => '7A', 'tingkat' => '7']);
        $tahun = TahunAjaran::aktif()->first();

        return compact('guru', 'kelas', 'tahun');
    }
}

test('tamu tidak dapat membuka daftar wali kelas', function () {
    $this->get(route('admin.wali-kelas.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka wali kelas', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.wali-kelas.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar wali kelas', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.wali-kelas.index'));
    $response->assertOk()->assertSee('Daftar Wali Kelas');
});

test('super-admin dapat menetapkan wali kelas', function () {
    $d = buatWaliDasar();

    $response = $this->actingAs(superAdmin())->post(route('admin.wali-kelas.store'), [
        'guru_id' => $d['guru']->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $d['tahun']->id,
    ]);

    $response->assertRedirect(route('admin.wali-kelas.index'));
    expect(WaliKelas::count())->toBe(1);
});

test('duplikat kelas-tahun ditolak', function () {
    $d = buatWaliDasar();
    WaliKelas::create([
        'guru_id' => $d['guru']->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $d['tahun']->id,
    ]);

    $user2 = User::factory()->create(['username' => 'guru-wali-2']);
    $guru2 = Guru::create(['user_id' => $user2->id, 'nama' => 'Guru Wali Dua', 'jenis_kelamin' => 'P', 'is_aktif' => true]);

    $response = $this->actingAs(superAdmin())->post(route('admin.wali-kelas.store'), [
        'guru_id' => $guru2->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $d['tahun']->id,
    ]);

    $response->assertSessionHasErrors('kelas_id');
    expect(WaliKelas::count())->toBe(1);
});

test('kelas sama boleh beda tahun', function () {
    $d = buatWaliDasar();
    WaliKelas::create([
        'guru_id' => $d['guru']->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $d['tahun']->id,
    ]);

    $tahunLalu = TahunAjaran::where('status_aktif', false)->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.wali-kelas.store'), [
        'guru_id' => $d['guru']->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $tahunLalu->id,
    ]);

    $response->assertRedirect(route('admin.wali-kelas.index'));
    expect(WaliKelas::count())->toBe(2);
});

test('admin tanpa permission delete tidak dapat menghapus wali', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $d = buatWaliDasar();
    $wali = WaliKelas::create([
        'guru_id' => $d['guru']->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $d['tahun']->id,
    ]);

    $this->actingAs($admin)->delete(route('admin.wali-kelas.destroy', $wali))->assertForbidden();
    expect(WaliKelas::find($wali->id))->not->toBeNull();
});
