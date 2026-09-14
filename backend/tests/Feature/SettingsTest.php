<?php

use App\Models\Pengaturan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
});

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

test('tamu tidak dapat membuka pengaturan', function () {
    $this->get(route('admin.pengaturans.index'))->assertRedirect(route('login'));
});

test('admin tanpa permission ditolak', function () {
    $user = User::factory()->create();
    $user->assignRole('guru');
    $this->actingAs($user)->get(route('admin.pengaturans.index'))->assertForbidden();
});

test('super-admin dapat membuka dan menyimpan pengaturan', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.pengaturans.index'));
    $response->assertOk()->assertSee('Pengaturan');

    $this->actingAs(superAdmin())->put(route('admin.pengaturans.update'), [
        'batas_terlambat' => '07:30',
        'semester_aktif' => 'genap',
        'batas_upload_mb' => 5,
    ])->assertRedirect(route('admin.pengaturans.index'));

    expect(Pengaturan::nilai('batas_terlambat'))->toBe('07:30')
        ->and(Pengaturan::nilai('semester_aktif'))->toBe('genap');
});

test('validasi menolak format salah', function () {
    $this->actingAs(superAdmin())->put(route('admin.pengaturans.update'), [
        'batas_terlambat' => 'xxx',
        'whatsapp_gateway_url' => 'bukan-url',
        'batas_upload_mb' => 999,
    ])->assertSessionHasErrors(['batas_terlambat', 'whatsapp_gateway_url', 'batas_upload_mb']);
});

test('re-trigger mengosongkan penanda harian', function () {
    Pengaturan::updateOrCreate(['kunci' => 'cek_belum_hadir_terakhir'], ['nilai' => now()->toDateString()]);

    $this->actingAs(superAdmin())->post(route('admin.pengaturans.reset'))
        ->assertRedirect(route('admin.pengaturans.index'));

    expect(Pengaturan::nilai('cek_belum_hadir_terakhir'))->toBeNull();
});

test('maintenance blokir non-super-admin', function () {
    Pengaturan::updateOrCreate(['kunci' => 'maintenance_mode'], ['nilai' => '1', 'keterangan' => 'test']);
    Pengaturan::updateOrCreate(['kunci' => 'maintenance_pesan'], ['nilai' => 'Maintenance test']);

    $user = User::factory()->create();
    $this->actingAs($user)->get('/')->assertStatus(503)->assertSee('Maintenance test');

    $this->actingAs(superAdmin())->get(route('admin.pengaturans.index'))->assertOk();
});
