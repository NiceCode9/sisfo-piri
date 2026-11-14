<?php

use App\Models\Menu;
use App\Models\User;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(MenuSeeder::class);
});

test('jurnalistik user sees jurnalistik menu items in sidebar', function () {
    $user = User::factory()->create();
    $user->assignRole('jurnalistik');

    $menus = Menu::parents()->userCanAccess($user)->get()->pluck('name')->toArray();

    expect($menus)->toContain('Jurnalistik');
    expect($menus)->toContain('Berita');
    expect($menus)->toContain('Kategori');
    expect($menus)->toContain('Tag');
    expect($menus)->toContain('Komentar');
    expect($menus)->toContain('Media');
});

test('siswa does not see jurnalistik menu items', function () {
    $user = User::factory()->create();
    $user->assignRole('siswa');

    $menus = Menu::parents()->userCanAccess($user)->get()->pluck('name')->toArray();

    expect($menus)->not->toContain('Jurnalistik');
    expect($menus)->not->toContain('Berita');
});
