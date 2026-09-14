<?php

use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('halaman dashboard admin dapat diakses user yang login', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin');

    $response->assertStatus(200);
    $response->assertSee('Nexus');
    $response->assertSee('id="sidebar"', false);
    $response->assertSee('id="mainContent"', false);
});

test('sidebar menampilkan menu publik dari database', function () {
    $user = User::factory()->create();
    Menu::create(['name' => 'Dashboard', 'icon' => 'fa-solid fa-th-large', 'route' => 'admin.dashboard', 'order' => 1]);

    $response = $this->actingAs($user)->get('/admin');

    $response->assertStatus(200);
    $response->assertSee('Dashboard');
});
