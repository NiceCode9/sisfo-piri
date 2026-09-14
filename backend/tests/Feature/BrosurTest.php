<?php

use App\Models\Brosur;
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

test('tamu tidak dapat membuka daftar brosur', function () {
    $this->get(route('admin.brosurs.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka brosur', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.brosurs.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar brosur', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.brosurs.index'));
    $response->assertOk()->assertSee('Daftar Brosur');
});

test('super-admin dapat menambah brosur dengan gambar', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.brosurs.store'), [
        'title' => 'Brosur Test',
        'desc' => 'Deskripsi test',
        'image' => UploadedFile::fake()->image('brosur.jpg'),
        'order' => 1,
        'is_active' => 1,
    ]);

    $response->assertRedirect(route('admin.brosurs.index'));
    $brosur = Brosur::where('title', 'Brosur Test')->first();
    expect($brosur)->not->toBeNull()
        ->and($brosur->is_active)->toBeTrue();
    Storage::disk('public')->assertExists($brosur->image_path);
});

test('file selain gambar ditolak', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.brosurs.store'), [
        'title' => 'Brosur PDF',
        'is_active' => 1,
        'image' => UploadedFile::fake()->create('dok.pdf', 100, 'application/pdf'),
    ]);

    $response->assertSessionHasErrors('image');
});

test('ganti gambar menghapus file lama', function () {
    $brosur = Brosur::create(['title' => 'Ganti', 'order' => 0, 'is_active' => true]);
    $lama = UploadedFile::fake()->image('lama.jpg')->store('brosur', 'public');
    $brosur->update(['image_path' => $lama]);
    Storage::disk('public')->assertExists($lama);

    $this->actingAs(superAdmin())->put(route('admin.brosurs.update', $brosur), [
        'title' => 'Ganti',
        'order' => 0,
        'is_active' => 1,
        'image' => UploadedFile::fake()->image('baru.jpg'),
    ])->assertRedirect(route('admin.brosurs.index'));

    Storage::disk('public')->assertMissing($lama);
    Storage::disk('public')->assertExists($brosur->fresh()->image_path);
});

test('hapus brosur menghapus file gambar', function () {
    $brosur = Brosur::create(['title' => 'Hapus', 'order' => 0, 'is_active' => true]);
    $path = UploadedFile::fake()->image('hapus.jpg')->store('brosur', 'public');
    $brosur->update(['image_path' => $path]);

    $this->actingAs(superAdmin())->delete(route('admin.brosurs.destroy', $brosur))
        ->assertRedirect(route('admin.brosurs.index'));

    expect(Brosur::find($brosur->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('admin tanpa permission delete tidak dapat menghapus', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $brosur = Brosur::create(['title' => 'Lindungi', 'order' => 0, 'is_active' => true]);

    $this->actingAs($admin)->delete(route('admin.brosurs.destroy', $brosur))->assertForbidden();
});
