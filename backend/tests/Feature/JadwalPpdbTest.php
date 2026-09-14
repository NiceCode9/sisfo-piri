<?php

use App\Models\JadwalPpdb;
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

test('tamu tidak dapat membuka daftar jadwal', function () {
    $this->get(route('admin.jadwal-ppdbs.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka daftar jadwal', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.jadwal-ppdbs.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar jadwal', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.jadwal-ppdbs.index'));
    $response->assertOk()->assertSee('Daftar Jadwal');
});

test('super-admin dapat menambah jadwal', function () {
    $tahun = TahunAjaran::aktif()->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.jadwal-ppdbs.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'nama_jadwal' => 'Fase Test',
        'tanggal_mulai' => '2026-09-01',
        'tanggal_selesai' => '2026-09-30',
        'keterangan' => 'Test',
    ]);

    $response->assertRedirect(route('admin.jadwal-ppdbs.index'));
    expect(JadwalPpdb::where('nama_jadwal', 'Fase Test')->first())->not->toBeNull();
});

test('validasi menolak tanggal selesai sebelum mulai', function () {
    $tahun = TahunAjaran::aktif()->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.jadwal-ppdbs.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'nama_jadwal' => 'Fase Test',
        'tanggal_mulai' => '2026-09-01',
        'tanggal_selesai' => '2026-08-31',
    ]);

    $response->assertSessionHasErrors('tanggal_selesai');
});

test('super-admin dapat memperbarui jadwal', function () {
    $jadwal = JadwalPpdb::first();

    $response = $this->actingAs(superAdmin())->put(route('admin.jadwal-ppdbs.update', $jadwal), [
        'tahun_ajaran_id' => $jadwal->tahun_ajaran_id,
        'nama_jadwal' => $jadwal->nama_jadwal,
        'tanggal_mulai' => $jadwal->tanggal_mulai,
        'tanggal_selesai' => $jadwal->tanggal_selesai,
        'keterangan' => 'Diperbarui',
    ]);

    $response->assertRedirect(route('admin.jadwal-ppdbs.index'));
    expect($jadwal->fresh()->keterangan)->toBe('Diperbarui');
});

test('super-admin dapat menghapus jadwal tanpa dependen', function () {
    $tahun = TahunAjaran::aktif()->first();
    $jadwal = JadwalPpdb::create([
        'tahun_ajaran_id' => $tahun->id,
        'nama_jadwal' => 'Fase Hapus',
        'tanggal_mulai' => '2026-09-01',
        'tanggal_selesai' => '2026-09-30',
    ]);

    $response = $this->actingAs(superAdmin())->delete(route('admin.jadwal-ppdbs.destroy', $jadwal));

    $response->assertRedirect(route('admin.jadwal-ppdbs.index'));
    expect(JadwalPpdb::find($jadwal->id))->toBeNull();
});

test('admin tanpa permission delete tidak dapat menghapus jadwal', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $jadwal = JadwalPpdb::first();

    $this->actingAs($admin)->delete(route('admin.jadwal-ppdbs.destroy', $jadwal))->assertForbidden();
});
