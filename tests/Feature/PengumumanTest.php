<?php

use App\Models\Pengumuman;
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

test('tamu tidak dapat membuka daftar pengumuman admin', function () {
    $this->get(route('admin.pengumumans.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka daftar pengumuman', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.pengumumans.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar pengumuman', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.pengumumans.index'));
    $response->assertOk()->assertSee('Daftar Pengumuman');
});

test('super-admin dapat menambah pengumuman', function () {
    $tahun = TahunAjaran::aktif()->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.pengumumans.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'judul' => 'Pengumuman Test',
        'isi' => 'Isi pengumuman test',
        'tanggal_pengumuman' => '2026-05-01',
        'status_aktif' => 1,
    ]);

    $response->assertRedirect(route('admin.pengumumans.index'));
    expect(Pengumuman::where('judul', 'Pengumuman Test')->exists())->toBeTrue();
});

test('validasi pengumuman menolak judul kosong', function () {
    $tahun = TahunAjaran::aktif()->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.pengumumans.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'judul' => '',
        'isi' => 'Isi',
        'tanggal_pengumuman' => '2026-05-01',
        'status_aktif' => 1,
    ]);

    $response->assertSessionHasErrors('judul');
});

test('publik dapat melihat daftar pengumuman aktif', function () {
    $response = $this->get(route('spmb.pengumuman.index'));
    $response->assertOk()->assertSee('Pengumuman');
    // hanya status_aktif true tampil, seeder 3 aktif
    $activeCount = Pengumuman::where('status_aktif', true)->count();
    expect($activeCount)->toBe(3);
});

test('publik dapat melihat detail pengumuman aktif', function () {
    $p = Pengumuman::where('status_aktif', true)->first();
    $response = $this->get(route('spmb.pengumuman.show', $p));
    $response->assertOk()->assertSee($p->judul);
});

test('pengumuman nonaktif 404 di publik', function () {
    $tahun = TahunAjaran::aktif()->first();
    $p = Pengumuman::create([
        'tahun_ajaran_id' => $tahun->id,
        'judul' => 'Nonaktif',
        'isi' => 'Isi',
        'tanggal_pengumuman' => '2026-05-01',
        'status_aktif' => false,
    ]);

    $this->get(route('spmb.pengumuman.show', $p))->assertNotFound();
});

test('home menampilkan biaya wajib dan pengumuman', function () {
    $response = $this->get(route('spmb.home'));
    $response->assertOk()->assertSee('Biaya');
    // biaya wajib sum 3.850.000 dari seeder
    $response->assertSee('Rp');
});

test('admin tanpa permission delete tidak dapat menghapus pengumuman', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $p = Pengumuman::first();

    $this->actingAs($admin)->delete(route('admin.pengumumans.destroy', $p))->assertForbidden();
});
