<?php

use App\Models\Galeri;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    Storage::fake('public');
});

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

test('tamu tidak dapat membuka daftar galeri', function () {
    $this->get(route('admin.galeris.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka galeri', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.galeris.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar galeri', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.galeris.index'));
    $response->assertOk()->assertSee('Daftar Galeri');
});

test('super-admin dapat menambah foto galeri', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.galeris.store'), [
        'tipe' => 'galeri',
        'title' => 'Suasana Belajar',
        'desc' => 'Kelas digital',
        'image' => UploadedFile::fake()->image('belajar.jpg'),
        'order' => 1,
        'is_active' => 1,
    ]);

    $response->assertRedirect(route('admin.galeris.index'));
    $galeri = Galeri::where('title', 'Suasana Belajar')->first();
    expect($galeri)->not->toBeNull()
        ->and($galeri->tipe)->toBe('galeri');
    Storage::disk('public')->assertExists($galeri->image_path);
});

test('foto wajib untuk tipe galeri', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.galeris.store'), [
        'tipe' => 'galeri',
        'title' => 'Tanpa Foto',
        'order' => 0,
        'is_active' => 1,
    ]);

    $response->assertSessionHasErrors('image');
});

test('prestasi tanpa foto tetap lolos bila tanggal diisi', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.galeris.store'), [
        'tipe' => 'prestasi',
        'title' => 'Juara OSN',
        'desc' => 'Juara 1 provinsi',
        'tanggal' => '2026-08-15',
        'order' => 0,
        'is_active' => 1,
    ]);

    $response->assertRedirect(route('admin.galeris.index'));
    expect(Galeri::where('title', 'Juara OSN')->first())->not->toBeNull();
});

test('ganti gambar menghapus file lama', function () {
    $galeri = Galeri::create(['tipe' => 'galeri', 'title' => 'Ganti', 'order' => 0, 'is_active' => true]);
    $lama = UploadedFile::fake()->image('lama.jpg')->store('galeri', 'public');
    $galeri->update(['image_path' => $lama]);
    Storage::disk('public')->assertExists($lama);

    $this->actingAs(superAdmin())->put(route('admin.galeris.update', $galeri), [
        'tipe' => 'galeri',
        'title' => 'Ganti',
        'order' => 0,
        'is_active' => 1,
        'image' => UploadedFile::fake()->image('baru.jpg'),
    ])->assertRedirect(route('admin.galeris.index'));

    Storage::disk('public')->assertMissing($lama);
    Storage::disk('public')->assertExists($galeri->fresh()->image_path);
});

test('admin tanpa permission delete tidak dapat menghapus', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $galeri = Galeri::create(['tipe' => 'galeri', 'title' => 'Lindungi', 'order' => 0, 'is_active' => true]);

    $this->actingAs($admin)->delete(route('admin.galeris.destroy', $galeri))->assertForbidden();
});
