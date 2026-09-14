<?php

use App\Models\MataPelajaran;
use App\Models\User;
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

test('tamu tidak dapat membuka daftar mapel', function () {
    $this->get(route('admin.mata-pelajarans.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka mapel', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.mata-pelajarans.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar mapel', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.mata-pelajarans.index'));
    $response->assertOk()->assertSee('Daftar Mata Pelajaran');
});

test('super-admin dapat menambah mapel', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.mata-pelajarans.store'), [
        'kode' => 'MTK',
        'nama' => 'Matematika',
        'kelompok' => 'A',
        'kkm' => 75,
        'is_aktif' => 1,
    ]);

    $response->assertRedirect(route('admin.mata-pelajarans.index'));
    expect(MataPelajaran::where('kode', 'MTK')->first())->not->toBeNull();
});

test('kode duplikat ditolak', function () {
    MataPelajaran::create(['kode' => 'MTK', 'nama' => 'Matematika', 'kelompok' => 'A', 'kkm' => 75]);

    $response = $this->actingAs(superAdmin())->post(route('admin.mata-pelajarans.store'), [
        'kode' => 'MTK',
        'nama' => 'Lain',
        'kelompok' => 'B',
        'kkm' => 70,
        'is_aktif' => 1,
    ]);

    $response->assertSessionHasErrors('kode');
});

test('kkm di luar 0-100 ditolak', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.mata-pelajarans.store'), [
        'kode' => 'IPA',
        'nama' => 'IPA',
        'kelompok' => 'A',
        'kkm' => 150,
        'is_aktif' => 1,
    ]);

    $response->assertSessionHasErrors('kkm');
});

test('super-admin dapat memperbarui mapel', function () {
    $mapel = MataPelajaran::create(['kode' => 'MTK', 'nama' => 'Matematika', 'kelompok' => 'A', 'kkm' => 75]);

    $response = $this->actingAs(superAdmin())->put(route('admin.mata-pelajarans.update', $mapel), [
        'kode' => 'MTK',
        'nama' => 'Matematika Baru',
        'kelompok' => 'A',
        'kkm' => 80,
        'is_aktif' => 1,
    ]);

    $response->assertRedirect(route('admin.mata-pelajarans.index'));
    expect($mapel->fresh()->nama)->toBe('Matematika Baru')
        ->and($mapel->fresh()->kkm)->toBe(80);
});

test('admin tanpa permission delete tidak dapat menghapus mapel', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $mapel = MataPelajaran::create(['kode' => 'MTK', 'nama' => 'Matematika', 'kelompok' => 'A', 'kkm' => 75]);

    $this->actingAs($admin)->delete(route('admin.mata-pelajarans.destroy', $mapel))->assertForbidden();
    expect(MataPelajaran::find($mapel->id))->not->toBeNull();
});
