<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('halaman login dapat ditampilkan', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertSee('Welcome Back');
});

test('user dapat login dengan username dan password yang benar', function () {
    $user = User::factory()->create(['username' => 'guru01']);

    $response = $this->post('/login', [
        'username' => 'guru01',
        'password' => 'password',
    ]);

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);
});

test('login dengan password salah ditolak', function () {
    User::factory()->create(['username' => 'guru01']);

    $response = $this->post('/login', [
        'username' => 'guru01',
        'password' => 'salah',
    ]);

    $response->assertSessionHasErrors('username');
    $this->assertGuest();
});

test('tamu tidak dapat mengakses halaman admin', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/login');
});

test('user yang login dapat mengakses halaman admin', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin');

    $response->assertStatus(200);
});

test('user dapat logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/login');
    $this->assertGuest();
});
