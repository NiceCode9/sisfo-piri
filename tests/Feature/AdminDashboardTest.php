<?php

use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('halaman dashboard admin dapat diakses', function () {
    $response = $this->get('/admin');

    $response->assertStatus(200);
    $response->assertSee('Nexus');
    $response->assertSee('id="sidebar"', false);
    $response->assertSee('id="mainContent"', false);
});

test('sidebar menampilkan menu publik dari database', function () {
    Menu::create(['name' => 'Dashboard', 'icon' => 'fa-solid fa-th-large', 'route' => 'admin.dashboard', 'order' => 1]);

    $response = $this->get('/admin');

    $response->assertStatus(200);
    $response->assertSee('Dashboard');
});
