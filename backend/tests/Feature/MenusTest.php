<?php

use App\Models\Menu;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

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

test('tamu tidak dapat membuka daftar menu', function () {
    $this->get(route('admin.menus.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka daftar menu', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.menus.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar menu', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.menus.index'));

    $response->assertOk();
    $response->assertSee('Daftar Menu');
});

test('super-admin dapat menambah menu dengan permission pivot dan legacy', function () {
    $perm = Permission::where('name', 'users.view')->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.menus.store'), [
        'name' => 'Laporan',
        'icon' => 'fa-solid fa-chart-line',
        'route' => 'admin.dashboard',
        'permission' => 'users.view',
        'permission_ids' => [$perm->id],
        'order' => 30,
        'is_active' => 1,
        'is_header' => 0,
    ]);

    $response->assertRedirect(route('admin.menus.index'));

    $menu = Menu::where('name', 'Laporan')->first();

    expect($menu)->not->toBeNull()
        ->and($menu->permission)->toBe('users.view')
        ->and($menu->permissions->pluck('id')->contains($perm->id))->toBeTrue();
});

test('nama duplikat ditolak saat tambah menu', function () {
    Menu::create(['name' => 'Laporan', 'order' => 1, 'is_active' => true, 'is_header' => false]);

    $response = $this->actingAs(superAdmin())->post(route('admin.menus.store'), [
        'name' => 'Laporan',
        'order' => 2,
        'is_active' => 1,
        'is_header' => 0,
    ]);

    $response->assertSessionHasErrors('name');
});

test('super-admin dapat memperbarui menu dan sync permission', function () {
    $menu = Menu::create(['name' => 'Laporan', 'order' => 1, 'is_active' => true, 'is_header' => false]);
    $permView = Permission::where('name', 'users.view')->first();
    $permMenu = Permission::where('name', 'menus.view')->first();
    $menu->permissions()->attach($permView);

    $response = $this->actingAs(superAdmin())->put(route('admin.menus.update', $menu), [
        'name' => 'Laporan Baru',
        'order' => 5,
        'is_active' => 1,
        'is_header' => 0,
        'permission' => null,
        'permission_ids' => [$permMenu->id],
    ]);

    $response->assertRedirect(route('admin.menus.index'));

    $fresh = $menu->fresh();

    expect($fresh->name)->toBe('Laporan Baru')
        ->and($fresh->permissions->pluck('name')->contains('menus.view'))->toBeTrue()
        ->and($fresh->permissions->pluck('name')->contains('users.view'))->toBeFalse();
});

test('validasi parent_id menolak diri sendiri saat update', function () {
    $menu = Menu::create(['name' => 'Parent', 'order' => 1, 'is_active' => true, 'is_header' => false]);

    $response = $this->actingAs(superAdmin())->put(route('admin.menus.update', $menu), [
        'name' => 'Parent',
        'parent_id' => $menu->id,
        'order' => 1,
        'is_active' => 1,
        'is_header' => 0,
    ]);

    $response->assertSessionHasErrors('parent_id');
});

test('super-admin dapat menghapus menu', function () {
    $menu = Menu::create(['name' => 'HapusSaya', 'order' => 99, 'is_active' => true, 'is_header' => false]);

    $response = $this->actingAs(superAdmin())->delete(route('admin.menus.destroy', $menu));

    $response->assertRedirect(route('admin.menus.index'));
    expect(Menu::where('name', 'HapusSaya')->exists())->toBeFalse();
});

test('admin tanpa permission delete tidak dapat menghapus menu', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $menu = Menu::create(['name' => 'HapusSaya', 'order' => 99, 'is_active' => true, 'is_header' => false]);

    $this->actingAs($admin)->delete(route('admin.menus.destroy', $menu))->assertForbidden();
    expect(Menu::where('name', 'HapusSaya')->exists())->toBeTrue();
});

test('super-admin dapat menambah menu header tanpa route', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.menus.store'), [
        'name' => 'Header Baru',
        'order' => 50,
        'is_active' => 1,
        'is_header' => 1,
    ]);

    $response->assertRedirect(route('admin.menus.index'));

    $menu = Menu::where('name', 'Header Baru')->first();

    expect($menu)->not->toBeNull()
        ->and($menu->is_header)->toBeTrue();
});
