<?php

use App\Models\Fasilitas;
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

if (! function_exists('superAdminFasilitas')) {
    function superAdminFasilitas(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

test('tamu tidak dapat membuka daftar fasilitas', function () {
    $this->get(route('admin.fasilitas.index'))->assertRedirect(route('login'));
});

test('super-admin dapat membuka daftar fasilitas', function () {
    $this->actingAs(superAdminFasilitas())->get(route('admin.fasilitas.index'))
        ->assertOk()->assertSee('Daftar Fasilitas');
});

test('super-admin dapat menambah fasilitas dengan banyak foto', function () {
    $response = $this->actingAs(superAdminFasilitas())->post(route('admin.fasilitas.store'), [
        'jenis' => 'Lab. IPA',
        'nama' => 'Lab IPA 1',
        'deskripsi' => 'Deskripsi test',
        'fotos' => [
            UploadedFile::fake()->image('a.jpg'),
            UploadedFile::fake()->image('b.jpg'),
        ],
        'urutan' => 1,
        'aktif' => 1,
    ]);

    $response->assertRedirect(route('admin.fasilitas.index'));
    $fasilitas = Fasilitas::where('nama', 'Lab IPA 1')->first();
    expect($fasilitas)->not->toBeNull()
        ->and($fasilitas->jenis)->toBe('Lab. IPA')
        ->and($fasilitas->aktif)->toBeTrue()
        ->and($fasilitas->fotos)->toHaveCount(2);

    foreach ($fasilitas->fotos as $path) {
        Storage::disk('public')->assertExists($path);
    }
});

test('file selain gambar ditolak', function () {
    $response = $this->actingAs(superAdminFasilitas())->post(route('admin.fasilitas.store'), [
        'jenis' => 'Lab. IPA',
        'nama' => 'Lab PDF',
        'aktif' => 1,
        'fotos' => [UploadedFile::fake()->create('dok.pdf', 100, 'application/pdf')],
    ]);

    $response->assertSessionHasErrors('fotos.0');
});

test('hapus foto lama saat update', function () {
    $fasilitas = Fasilitas::create([
        'jenis' => 'Lab. IPA',
        'nama' => 'Hapus Foto',
        'aktif' => true,
        'urutan' => 0,
    ]);
    $lama = UploadedFile::fake()->image('lama.jpg')->store('fasilitas', 'public');
    $fasilitas->update(['fotos' => [$lama]]);
    Storage::disk('public')->assertExists($lama);

    $this->actingAs(superAdminFasilitas())->put(route('admin.fasilitas.update', $fasilitas), [
        'jenis' => 'Lab. IPA',
        'nama' => 'Hapus Foto',
        'urutan' => 0,
        'aktif' => 1,
        'hapus_foto' => [$lama],
    ])->assertRedirect(route('admin.fasilitas.index'));

    expect($fasilitas->fresh()->fotos)->toBeNull();
    Storage::disk('public')->assertMissing($lama);
});

test('hapus fasilitas menghapus semua foto', function () {
    $fasilitas = Fasilitas::create([
        'jenis' => 'Lab. IPA',
        'nama' => 'Hapus Item',
        'aktif' => true,
        'urutan' => 0,
    ]);
    $path = UploadedFile::fake()->image('hapus.jpg')->store('fasilitas', 'public');
    $fasilitas->update(['fotos' => [$path]]);

    $this->actingAs(superAdminFasilitas())->delete(route('admin.fasilitas.destroy', $fasilitas))
        ->assertRedirect(route('admin.fasilitas.index'));

    expect(Fasilitas::find($fasilitas->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});
