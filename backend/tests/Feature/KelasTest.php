<?php

use App\Models\Kelas;
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

test('tamu tidak dapat membuka daftar kelas', function () {
    $this->get(route('admin.kelas.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka kelas', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.kelas.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar kelas', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.kelas.index'));
    $response->assertOk()->assertSee('Daftar Kelas');
});

test('super-admin dapat menambah kelas', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.kelas.store'), [
        'nama_kelas' => '7A',
        'tingkat' => '7',
        'deskripsi' => 'Kelas tujuh A',
    ]);

    $response->assertRedirect(route('admin.kelas.index'));
    expect(Kelas::where('nama_kelas', '7A')->first())->not->toBeNull();
});

test('nama duplikat ditolak', function () {
    Kelas::create(['nama_kelas' => '7A', 'tingkat' => '7']);

    $response = $this->actingAs(superAdmin())->post(route('admin.kelas.store'), [
        'nama_kelas' => '7A',
        'tingkat' => '7',
    ]);

    $response->assertSessionHasErrors('nama_kelas');
});

test('tingkat selain 7-9 ditolak', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.kelas.store'), [
        'nama_kelas' => '10A',
        'tingkat' => '10',
    ]);

    $response->assertSessionHasErrors('tingkat');
});

test('super-admin dapat memperbarui kelas', function () {
    $kelas = Kelas::create(['nama_kelas' => '7A', 'tingkat' => '7']);

    $response = $this->actingAs(superAdmin())->put(route('admin.kelas.update', $kelas), [
        'nama_kelas' => '7A',
        'tingkat' => '7',
        'deskripsi' => 'Diperbarui',
    ]);

    $response->assertRedirect(route('admin.kelas.index'));
    expect($kelas->fresh()->deskripsi)->toBe('Diperbarui');
});

test('admin tanpa permission delete tidak dapat menghapus kelas', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $kelas = Kelas::create(['nama_kelas' => '7B', 'tingkat' => '7']);

    $this->actingAs($admin)->delete(route('admin.kelas.destroy', $kelas))->assertForbidden();
    expect(Kelas::find($kelas->id))->not->toBeNull();
});
