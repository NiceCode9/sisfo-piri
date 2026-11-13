<?php

use App\Models\Berita;
use App\Models\KategoriBerita;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
});

test('jurnalistik bisa membuat berita', function () {
    $user = User::factory()->create();
    $user->assignRole('jurnalistik');
    $kategori = KategoriBerita::factory()->create();

    $this->actingAs($user)
        ->post(route('jurnalistik.beritas.store'), [
            'judul' => 'Uji Jurnalistik',
            'kategori_berita_id' => $kategori->id,
            'status' => 'draft',
            'konten' => '<p>Isi berita</p>',
        ])
        ->assertRedirect(route('jurnalistik.beritas.index'));

    expect(Berita::where('judul', 'Uji Jurnalistik')->exists())->toBeTrue();
});

test('siswa tidak bisa membuka dashboard jurnalistik', function () {
    $user = User::factory()->create();
    $user->assignRole('siswa');

    $this->actingAs($user)->get(route('jurnalistik.dashboard'))->assertForbidden();
});
