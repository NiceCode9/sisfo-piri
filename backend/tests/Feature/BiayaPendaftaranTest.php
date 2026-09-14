<?php

use App\Models\BiayaPendaftaran;
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

test('tamu tidak dapat membuka daftar biaya', function () {
    $this->get(route('admin.biaya-pendaftarans.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka daftar biaya', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.biaya-pendaftarans.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar biaya', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.biaya-pendaftarans.index'));
    $response->assertOk()->assertSee('Daftar Biaya');
});

test('super-admin dapat menambah biaya', function () {
    $tahun = TahunAjaran::aktif()->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.biaya-pendaftarans.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'jenis_biaya' => 'Biaya Test',
        'jumlah' => 500000,
        'mata_uang' => 'IDR',
        'wajib_bayar' => 1,
        'dapat_diangsur' => 0,
        'keterangan' => 'Test',
    ]);

    $response->assertRedirect(route('admin.biaya-pendaftarans.index'));
    expect(BiayaPendaftaran::where('jenis_biaya', 'Biaya Test')->exists())->toBeTrue();
});

test('validasi biaya menolak jumlah negatif', function () {
    $tahun = TahunAjaran::aktif()->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.biaya-pendaftarans.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'jenis_biaya' => 'Gagal',
        'jumlah' => -100,
        'wajib_bayar' => 1,
        'dapat_diangsur' => 0,
    ]);

    $response->assertSessionHasErrors('jumlah');
});

test('super-admin dapat memperbarui biaya', function () {
    $biaya = BiayaPendaftaran::first();

    $response = $this->actingAs(superAdmin())->put(route('admin.biaya-pendaftarans.update', $biaya), [
        'tahun_ajaran_id' => $biaya->tahun_ajaran_id,
        'jenis_biaya' => $biaya->jenis_biaya.' Updated',
        'jumlah' => $biaya->jumlah,
        'wajib_bayar' => $biaya->wajib_bayar,
        'dapat_diangsur' => $biaya->dapat_diangsur,
    ]);

    $response->assertRedirect(route('admin.biaya-pendaftarans.index'));
    expect($biaya->fresh()->jenis_biaya)->toContain('Updated');
});

test('super-admin dapat menghapus biaya', function () {
    $tahun = TahunAjaran::aktif()->first();
    $biaya = BiayaPendaftaran::create([
        'tahun_ajaran_id' => $tahun->id,
        'jenis_biaya' => 'Hapus Test',
        'jumlah' => 100000,
        'wajib_bayar' => true,
        'dapat_diangsur' => false,
    ]);

    $response = $this->actingAs(superAdmin())->delete(route('admin.biaya-pendaftarans.destroy', $biaya));
    $response->assertRedirect(route('admin.biaya-pendaftarans.index'));
    expect(BiayaPendaftaran::where('jenis_biaya', 'Hapus Test')->exists())->toBeFalse();
});

test('admin tanpa permission delete tidak dapat menghapus biaya', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $biaya = BiayaPendaftaran::first();

    $this->actingAs($admin)->delete(route('admin.biaya-pendaftarans.destroy', $biaya))->assertForbidden();
});
