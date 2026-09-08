<?php

use App\Models\Gelombang;
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

test('tamu tidak dapat membuka daftar gelombang', function () {
    $this->get(route('admin.gelombangs.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka daftar gelombang', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.gelombangs.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar gelombang', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.gelombangs.index'));
    $response->assertOk()->assertSee('Daftar Gelombang');
});

test('super-admin dapat menambah gelombang', function () {
    $tahun = TahunAjaran::aktif()->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.gelombangs.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'nama_gelombang' => 'Gelombang Test',
        'nomor_urut' => 99,
        'badge' => 'TEST',
        'tanggal_buka' => '2026-09-01',
        'tanggal_tutup' => '2026-09-30',
        'tanggal_tes' => '2026-10-05',
        'tanggal_pengumuman' => '2026-10-10',
        'kuota' => 50,
        'terisi' => 0,
        'diskon_persen' => 10,
        'keuntungan' => ['Gratis tas', 'Prioritas'],
        'keterangan' => 'Test',
        'warna_border' => 'primary-600',
        'is_aktif' => 1,
    ]);

    $response->assertRedirect(route('admin.gelombangs.index'));
    $gel = Gelombang::where('nama_gelombang', 'Gelombang Test')->first();
    expect($gel)->not->toBeNull()
        ->and($gel->keuntungan)->toBe(['Gratis tas', 'Prioritas'])
        ->and($gel->is_aktif)->toBeTrue();
});

test('validasi gelombang menolak tanggal tutup sebelum buka', function () {
    $tahun = TahunAjaran::aktif()->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.gelombangs.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'nama_gelombang' => 'Gagal',
        'nomor_urut' => 99,
        'tanggal_buka' => '2026-10-10',
        'tanggal_tutup' => '2026-10-01',
        'kuota' => 10,
        'is_aktif' => 1,
    ]);

    $response->assertSessionHasErrors('tanggal_tutup');
});

test('validasi diskon melebihi 100 ditolak', function () {
    $tahun = TahunAjaran::aktif()->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.gelombangs.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'nama_gelombang' => 'Gagal Diskon',
        'nomor_urut' => 99,
        'tanggal_buka' => '2026-10-01',
        'tanggal_tutup' => '2026-10-31',
        'kuota' => 10,
        'diskon_persen' => 150,
        'is_aktif' => 1,
    ]);

    $response->assertSessionHasErrors('diskon_persen');
});

test('super-admin dapat memperbarui gelombang', function () {
    $gel = Gelombang::first();

    $response = $this->actingAs(superAdmin())->put(route('admin.gelombangs.update', $gel), [
        'tahun_ajaran_id' => $gel->tahun_ajaran_id,
        'nama_gelombang' => $gel->nama_gelombang.' Updated',
        'nomor_urut' => $gel->nomor_urut,
        'tanggal_buka' => $gel->tanggal_buka->format('Y-m-d'),
        'tanggal_tutup' => $gel->tanggal_tutup->format('Y-m-d'),
        'kuota' => $gel->kuota,
        'is_aktif' => 1,
    ]);

    $response->assertRedirect(route('admin.gelombangs.index'));
    expect($gel->fresh()->nama_gelombang)->toContain('Updated');
});

test('super-admin dapat menghapus gelombang', function () {
    $tahun = TahunAjaran::aktif()->first();
    $gel = Gelombang::create([
        'tahun_ajaran_id' => $tahun->id,
        'nama_gelombang' => 'Hapus Test',
        'nomor_urut' => 99,
        'tanggal_buka' => '2026-09-01',
        'tanggal_tutup' => '2026-09-30',
        'kuota' => 10,
        'is_aktif' => true,
    ]);

    $response = $this->actingAs(superAdmin())->delete(route('admin.gelombangs.destroy', $gel));
    $response->assertRedirect(route('admin.gelombangs.index'));
    expect(Gelombang::where('nama_gelombang', 'Hapus Test')->exists())->toBeFalse();
});

test('admin tanpa permission delete tidak dapat menghapus gelombang', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $gel = Gelombang::first();

    $this->actingAs($admin)->delete(route('admin.gelombangs.destroy', $gel))->assertForbidden();
    expect(Gelombang::find($gel->id))->not->toBeNull();
});
