<?php

use App\Models\ProfilSekolah;
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

test('tamu tidak dapat membuka form profil', function () {
    $this->get(route('admin.profil-sekolah.edit'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka form profil', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.profil-sekolah.edit'))->assertForbidden();
});

test('super-admin dapat membuka form profil', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.profil-sekolah.edit'));
    $response->assertOk()->assertSee('Profil Sekolah');
});

test('super-admin dapat memperbarui identitas dan misi', function () {
    ProfilSekolah::create(['nama_sekolah' => 'SMKN Ngaglik']);

    $response = $this->actingAs(superAdmin())->put(route('admin.profil-sekolah.update'), [
        'nama_sekolah' => 'SMKN Ngaglik Baru',
        'telp' => '(0274) 123456',
        'email' => 'info@smkn.sch.id',
        'tahun_berdiri' => 2005,
        'akreditasi' => 'A',
        'visi' => 'Visi baru',
        'misi' => ['Misi satu', 'Misi dua'],
        'nama_kepala' => 'Budi Kepala',
    ]);

    $response->assertRedirect(route('admin.profil-sekolah.edit'));
    $profil = ProfilSekolah::first();
    expect($profil->nama_sekolah)->toBe('SMKN Ngaglik Baru')
        ->and($profil->misi)->toBe(['Misi satu', 'Misi dua'])
        ->and(ProfilSekolah::count())->toBe(1);
});

test('ganti logo menghapus file lama', function () {
    $profil = ProfilSekolah::create(['nama_sekolah' => 'SMKN Ngaglik']);
    $lama = UploadedFile::fake()->image('lama.png')->store('profil', 'public');
    $profil->update(['logo_path' => $lama]);
    Storage::disk('public')->assertExists($lama);

    $this->actingAs(superAdmin())->put(route('admin.profil-sekolah.update'), [
        'nama_sekolah' => 'SMKN Ngaglik',
        'logo' => UploadedFile::fake()->image('baru.png'),
    ])->assertRedirect(route('admin.profil-sekolah.edit'));

    Storage::disk('public')->assertMissing($lama);
    Storage::disk('public')->assertExists($profil->fresh()->logo_path);
});

test('maps url selain google embed ditolak', function () {
    ProfilSekolah::create(['nama_sekolah' => 'SMKN Ngaglik']);

    $response = $this->actingAs(superAdmin())->put(route('admin.profil-sekolah.update'), [
        'nama_sekolah' => 'SMKN Ngaglik',
        'maps_embed_url' => 'https://evil.example.com/x',
    ]);

    $response->assertSessionHasErrors('maps_embed_url');
});

test('halaman about menampilkan nama dari database', function () {
    ProfilSekolah::create([
        'nama_sekolah' => 'SMKN Ngaglik Asli',
        'tahun_berdiri' => 2005,
        'akreditasi' => 'A',
        'misi' => ['Misi DB Satu', 'Misi DB Dua'],
    ]);

    $response = $this->get(route('spmb.about'));

    $response->assertOk()
        ->assertSee('SMKN Ngaglik Asli')
        ->assertSee('Misi DB Satu');
});
