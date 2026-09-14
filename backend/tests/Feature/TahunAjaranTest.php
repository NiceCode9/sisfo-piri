<?php

use App\Models\TahunAjaran;
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

test('tamu tidak dapat membuka daftar tahun ajaran', function () {
    $this->get(route('admin.tahun-ajarans.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka daftar tahun ajaran', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.tahun-ajarans.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar tahun ajaran', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.tahun-ajarans.index'));
    $response->assertOk()->assertSee('Daftar Tahun Ajaran');
});

test('super-admin dapat menambah tahun ajaran', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.tahun-ajarans.store'), [
        'nama_tahun_ajaran' => '2027/2028',
        'tanggal_mulai' => '2027-07-01',
        'tanggal_selesai' => '2028-06-30',
        'status_aktif' => 0,
    ]);

    $response->assertRedirect(route('admin.tahun-ajarans.index'));
    expect(TahunAjaran::where('nama_tahun_ajaran', '2027/2028')->first())->not->toBeNull();
});

test('mengaktifkan tahun menonaktifkan tahun lain', function () {
    $aktifLama = TahunAjaran::aktif()->first();
    expect($aktifLama)->not->toBeNull();

    $this->actingAs(superAdmin())->post(route('admin.tahun-ajarans.store'), [
        'nama_tahun_ajaran' => '2027/2028',
        'tanggal_mulai' => '2027-07-01',
        'tanggal_selesai' => '2028-06-30',
        'status_aktif' => 1,
    ])->assertRedirect(route('admin.tahun-ajarans.index'));

    expect($aktifLama->fresh()->status_aktif)->toBeFalse();
    expect(TahunAjaran::aktif()->count())->toBe(1);
});

test('validasi menolak tanggal selesai sebelum mulai', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.tahun-ajarans.store'), [
        'nama_tahun_ajaran' => '2027/2028',
        'tanggal_mulai' => '2027-07-01',
        'tanggal_selesai' => '2027-06-30',
        'status_aktif' => 0,
    ]);

    $response->assertSessionHasErrors('tanggal_selesai');
});

test('nama tahun duplikat ditolak', function () {
    $ada = TahunAjaran::first();

    $response = $this->actingAs(superAdmin())->post(route('admin.tahun-ajarans.store'), [
        'nama_tahun_ajaran' => $ada->nama_tahun_ajaran,
        'tanggal_mulai' => '2027-07-01',
        'tanggal_selesai' => '2028-06-30',
        'status_aktif' => 0,
    ]);

    $response->assertSessionHasErrors('nama_tahun_ajaran');
});

test('hapus tahun berelasi diblokir', function () {
    $tahun = TahunAjaran::aktif()->first();

    $response = $this->actingAs(superAdmin())->delete(route('admin.tahun-ajarans.destroy', $tahun));

    $response->assertSessionHas('error');
    expect(TahunAjaran::find($tahun->id))->not->toBeNull();
});

test('admin tanpa permission delete tidak dapat menghapus tahun', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $tahun = TahunAjaran::create([
        'nama_tahun_ajaran' => '2029/2030',
        'tanggal_mulai' => '2029-07-01',
        'tanggal_selesai' => '2030-06-30',
        'status_aktif' => false,
    ]);

    $this->actingAs($admin)->delete(route('admin.tahun-ajarans.destroy', $tahun))->assertForbidden();
});
