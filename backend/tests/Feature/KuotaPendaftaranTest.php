<?php

use App\Models\JalurPendaftaran;
use App\Models\KuotaPendaftaran;
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

test('tamu tidak dapat membuka daftar kuota', function () {
    $this->get(route('admin.kuota-pendaftarans.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka daftar kuota', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.kuota-pendaftarans.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar kuota', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.kuota-pendaftarans.index'));
    $response->assertOk()->assertSee('Daftar Kuota');
});

test('super-admin dapat menambah kuota', function () {
    $tahun = TahunAjaran::aktif()->first();
    $jalur = JalurPendaftaran::doesntHave('kuotaPendaftaran')->first()
        ?? JalurPendaftaran::create(['nama_jalur' => 'Jalur Baru', 'aktif' => true]);

    // Pastikan kombinasi belum ada
    KuotaPendaftaran::where('tahun_ajaran_id', $tahun->id)->where('jalur_pendaftaran_id', $jalur->id)->delete();

    $response = $this->actingAs(superAdmin())->post(route('admin.kuota-pendaftarans.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'jalur_pendaftaran_id' => $jalur->id,
        'kuota' => 50,
        'terisi' => 5,
        'keterangan' => 'Test',
    ]);

    $response->assertRedirect(route('admin.kuota-pendaftarans.index'));
    $kuota = KuotaPendaftaran::where('tahun_ajaran_id', $tahun->id)->where('jalur_pendaftaran_id', $jalur->id)->first();
    expect($kuota)->not->toBeNull()->and($kuota->terisi)->toBe(5);
});

test('duplikat kombinasi tahun dan jalur ditolak', function () {
    $ada = KuotaPendaftaran::first();

    $response = $this->actingAs(superAdmin())->post(route('admin.kuota-pendaftarans.store'), [
        'tahun_ajaran_id' => $ada->tahun_ajaran_id,
        'jalur_pendaftaran_id' => $ada->jalur_pendaftaran_id,
        'kuota' => 10,
    ]);

    $response->assertSessionHasErrors('jalur_pendaftaran_id');
});

test('terisi melebihi kuota ditolak', function () {
    $tahun = TahunAjaran::aktif()->first();
    $jalur = JalurPendaftaran::create(['nama_jalur' => 'Jalur Batas', 'aktif' => true]);

    $response = $this->actingAs(superAdmin())->post(route('admin.kuota-pendaftarans.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'jalur_pendaftaran_id' => $jalur->id,
        'kuota' => 10,
        'terisi' => 11,
    ]);

    $response->assertSessionHasErrors('terisi');
});

test('hapus kuota yang terisi diblokir', function () {
    $kuota = KuotaPendaftaran::where('terisi', '>', 0)->first();

    $response = $this->actingAs(superAdmin())->delete(route('admin.kuota-pendaftarans.destroy', $kuota));

    $response->assertSessionHas('error');
    expect(KuotaPendaftaran::find($kuota->id))->not->toBeNull();
});

test('admin tanpa permission delete tidak dapat menghapus kuota', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $kuota = KuotaPendaftaran::where('terisi', 0)->first()
        ?? KuotaPendaftaran::first();

    $this->actingAs($admin)->delete(route('admin.kuota-pendaftarans.destroy', $kuota))->assertForbidden();
});
