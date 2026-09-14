<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
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

test('tamu tidak dapat membuka whatsapp', function () {
    $this->get(route('admin.whatsapp.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak', function () {
    $user = User::factory()->create();
    $user->assignRole('guru');
    $this->actingAs($user)->get(route('admin.whatsapp.index'))->assertForbidden();
});

test('super-admin dapat membuka halaman whatsapp', function () {
    Http::fake(fn () => Http::response(['ok' => true, 'siap' => false, 'qrTersedia' => false], 200));

    $this->actingAs(superAdmin())->get(route('admin.whatsapp.index'))->assertOk()->assertSee('WhatsApp Gateway');
});

test('qr proxy meneruskan png dari gateway', function () {
    Http::fake(fn () => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png']));

    $response = $this->actingAs(superAdmin())->get(route('admin.whatsapp.qr'));
    $response->assertOk()->assertHeader('Content-Type', 'image/png');
});

test('disconnect meneruskan ke gateway', function () {
    Http::fake(fn () => Http::response(['ok' => true], 200));

    $this->actingAs(superAdmin())->post(route('admin.whatsapp.disconnect'))
        ->assertRedirect(route('admin.whatsapp.index'));

    Http::assertSent(fn ($r) => str_contains($r->url(), '/logout'));
});

test('gateway down tampil tak terhubung', function () {
    Http::fake(fn () => Http::response(null, 500));

    $this->actingAs(superAdmin())->get(route('admin.whatsapp.index'))
        ->assertOk()->assertSee('Tak terhubung');
});
