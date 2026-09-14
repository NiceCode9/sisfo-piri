<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();

        $user->assignRole('super-admin');

        return $user;
    }
}

test('tamu tidak dapat membuka daftar peran', function () {
    $this->get(route('admin.roles.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka daftar peran', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar peran', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.roles.index'));

    $response->assertOk();
    $response->assertSee('Daftar Peran');
});

test('super-admin dapat menambah peran beserta permission', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.roles.store'), [
        'name' => 'editor',
        'permissions' => ['users.view', 'roles.view'],
    ]);

    $response->assertRedirect(route('admin.roles.index'));

    $role = Role::where('name', 'editor')->first();

    expect($role)->not->toBeNull()
        ->and($role->hasPermissionTo('users.view'))->toBeTrue()
        ->and($role->hasPermissionTo('roles.view'))->toBeTrue();
});

test('nama duplikat ditolak saat tambah peran', function () {
    Role::create(['name' => 'editor', 'guard_name' => 'web']);

    $response = $this->actingAs(superAdmin())->post(route('admin.roles.store'), [
        'name' => 'editor',
    ]);

    $response->assertSessionHasErrors('name');
});

test('super-admin dapat memperbarui peran dan sync permission', function () {
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $role->givePermissionTo('users.view');

    $response = $this->actingAs(superAdmin())->put(route('admin.roles.update', $role), [
        'name' => 'editor-baru',
        'permissions' => ['menus.view'],
    ]);

    $response->assertRedirect(route('admin.roles.index'));

    $fresh = $role->fresh();

    expect($fresh->name)->toBe('editor-baru')
        ->and($fresh->hasPermissionTo('menus.view'))->toBeTrue()
        ->and($fresh->hasPermissionTo('users.view'))->toBeFalse();
});

test('tidak dapat menghapus satu-satunya super-admin', function () {
    $admin = superAdmin();

    // Role super-admin masih satu, hapus harus ditolak
    $response = $this->actingAs($admin)->delete(route('admin.roles.destroy', Role::where('name', 'super-admin')->first()));

    $response->assertSessionHas('error');
    expect(Role::where('name', 'super-admin')->exists())->toBeTrue();
});

test('super-admin dapat menghapus peran biasa', function () {
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    $response = $this->actingAs(superAdmin())->delete(route('admin.roles.destroy', $role));

    $response->assertRedirect(route('admin.roles.index'));
    expect(Role::where('name', 'editor')->exists())->toBeFalse();
});

test('admin tanpa permission delete tidak dapat menghapus peran', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    $this->actingAs($admin)->delete(route('admin.roles.destroy', $role))->assertForbidden();
    expect(Role::where('name', 'editor')->exists())->toBeTrue();
});

test('validasi nama peran menolak format tidak valid', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.roles.store'), [
        'name' => 'nama dengan spasi',
    ]);

    $response->assertSessionHasErrors('name');
});
