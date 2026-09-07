<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

function superAdmin(): User
{
    $user = User::factory()->create();

    $user->assignRole('super-admin');

    return $user;
}

test('tamu tidak dapat membuka daftar pengguna', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka daftar pengguna', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar pengguna', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.users.index'));

    $response->assertOk();
    $response->assertSee('Daftar Pengguna');
});

test('super-admin dapat menambah pengguna beserta role', function () {
    Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);

    $response = $this->actingAs(superAdmin())->post(route('admin.users.store'), [
        'username' => 'guru01',
        'name' => 'Guru Satu',
        'email' => 'guru01@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'roles' => ['guru'],
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $user = User::where('username', 'guru01')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('guru'))->toBeTrue();
});

test('username duplikat ditolak saat tambah pengguna', function () {
    User::factory()->create(['username' => 'guru01']);

    $response = $this->actingAs(superAdmin())->post(route('admin.users.store'), [
        'username' => 'guru01',
        'name' => 'Duplikat',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('username');
});

test('super-admin dapat memperbarui pengguna tanpa mengubah password', function () {
    $target = User::factory()->create(['username' => 'guru01']);
    $hash = $target->password;

    $response = $this->actingAs(superAdmin())->put(route('admin.users.update', $target), [
        'username' => 'guru01',
        'name' => 'Nama Baru',
        'password' => null,
        'password_confirmation' => null,
    ]);

    $response->assertRedirect(route('admin.users.index'));

    expect($target->fresh()->name)->toBe('Nama Baru')
        ->and($target->fresh()->password)->toBe($hash);
});

test('tidak dapat menghapus akun sendiri', function () {
    $admin = superAdmin();

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(User::find($admin->id))->not->toBeNull();
});

test('tidak dapat menghapus satu-satunya super-admin', function () {
    $admin = superAdmin();
    $other = superAdmin();

    $response = $this->actingAs($other)->delete(route('admin.users.destroy', $admin));

    // Masih ada satu super-admin lain, penghapusan boleh.
    $response->assertRedirect(route('admin.users.index'));

    $lastResponse = $this->actingAs($other)->delete(route('admin.users.destroy', $other));

    $lastResponse->assertSessionHas('error');
    expect(User::find($other->id))->not->toBeNull();
});

test('admin tanpa permission delete tidak dapat menghapus pengguna', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $target))->assertForbidden();
    expect(User::find($target->id))->not->toBeNull();
});
