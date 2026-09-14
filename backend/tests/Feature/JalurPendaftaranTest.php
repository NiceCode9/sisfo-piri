<?php

use App\Models\JalurPendaftaran;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
});

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

test('tamu tidak dapat membuka daftar jalur', function () {
    $this->get(route('admin.jalur-pendaftarans.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka daftar jalur', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.jalur-pendaftarans.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar jalur', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.jalur-pendaftarans.index'));
    $response->assertOk()->assertSee('Daftar Jalur');
});

test('super-admin dapat menambah jalur', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.jalur-pendaftarans.store'), [
        'nama_jalur' => 'Jalur Test',
        'deskripsi' => 'Deskripsi test',
        'aktif' => 1,
        'wajib_sertifikat' => 0,
    ]);

    $response->assertRedirect(route('admin.jalur-pendaftarans.index'));
    $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Test')->first();
    expect($jalur)->not->toBeNull()->and($jalur->aktif)->toBeTrue();
});

test('nama jalur duplikat ditolak', function () {
    $ada = JalurPendaftaran::first();

    $response = $this->actingAs(superAdmin())->post(route('admin.jalur-pendaftarans.store'), [
        'nama_jalur' => $ada->nama_jalur,
        'aktif' => 1,
        'wajib_sertifikat' => 0,
    ]);

    $response->assertSessionHasErrors('nama_jalur');
});

test('super-admin dapat memperbarui jalur', function () {
    $jalur = JalurPendaftaran::first();

    $response = $this->actingAs(superAdmin())->put(route('admin.jalur-pendaftarans.update', $jalur), [
        'nama_jalur' => $jalur->nama_jalur,
        'deskripsi' => 'Diperbarui',
        'aktif' => 0,
        'wajib_sertifikat' => 1,
    ]);

    $response->assertRedirect(route('admin.jalur-pendaftarans.index'));
    expect($jalur->fresh()->deskripsi)->toBe('Diperbarui')->and($jalur->fresh()->aktif)->toBeFalse()->and($jalur->fresh()->wajib_sertifikat)->toBeTrue();
});

test('hapus jalur berelasi diblokir', function () {
    $jalur = JalurPendaftaran::has('kuotaPendaftaran')->first();

    $response = $this->actingAs(superAdmin())->delete(route('admin.jalur-pendaftarans.destroy', $jalur));

    $response->assertSessionHas('error');
    expect(JalurPendaftaran::find($jalur->id))->not->toBeNull();
});

test('admin tanpa permission delete tidak dapat menghapus jalur', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $jalur = JalurPendaftaran::create(['nama_jalur' => 'Jalur Hapus', 'aktif' => true]);

    $this->actingAs($admin)->delete(route('admin.jalur-pendaftarans.destroy', $jalur))->assertForbidden();
});
