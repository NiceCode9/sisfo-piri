<?php

use App\Models\Ekstrakurikuler;
use App\Models\User;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
});

if (! function_exists('superAdminEkskul')) {
    function superAdminEkskul(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

test('tamu tidak dapat membuka daftar ekstrakurikuler', function () {
    $this->get(route('admin.ekstrakurikulers.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka daftar ekstrakurikuler', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.ekstrakurikulers.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar ekstrakurikuler', function () {
    $this->actingAs(superAdminEkskul())->get(route('admin.ekstrakurikulers.index'))
        ->assertOk()
        ->assertSee('Daftar Ekstrakurikuler');
});

test('super-admin dapat menambah ekstrakurikuler dengan pembina guru', function () {
    $response = $this->actingAs(superAdminEkskul())->post(route('admin.ekstrakurikulers.store'), [
        'kode' => 'BADMINTON',
        'nama' => 'Badminton',
        'pembina_pilih' => 'Guru A',
        'jadwal' => 'Selasa 15:00-17:00',
        'is_aktif' => '1',
    ]);

    $response->assertRedirect(route('admin.ekstrakurikulers.index'));
    expect(Ekstrakurikuler::where('kode', 'BADMINTON')->first()?->pembina)->toBe('Guru A');
});

test('pembina luar sekolah disimpan dari input manual', function () {
    $response = $this->actingAs(superAdminEkskul())->post(route('admin.ekstrakurikulers.store'), [
        'kode' => 'KARATE',
        'nama' => 'Karate',
        'pembina_pilih' => '__luar__',
        'pembina_manual' => 'Sensei Takeshi',
        'is_aktif' => '1',
    ]);

    $response->assertRedirect(route('admin.ekstrakurikulers.index'));
    expect(Ekstrakurikuler::where('kode', 'KARATE')->first()?->pembina)->toBe('Sensei Takeshi');
});

test('pembina luar tanpa nama manual ditolak', function () {
    $this->actingAs(superAdminEkskul())->post(route('admin.ekstrakurikulers.store'), [
        'kode' => 'SILAT',
        'nama' => 'Silat',
        'pembina_pilih' => '__luar__',
        'is_aktif' => '1',
    ])->assertSessionHasErrors('pembina_manual');
});

test('kode duplikat ditolak', function () {
    $this->actingAs(superAdminEkskul())->post(route('admin.ekstrakurikulers.store'), [
        'kode' => 'PRAMUKA',
        'nama' => 'Pramuka Duplikat',
        'is_aktif' => '1',
    ])->assertSessionHasErrors('kode');
});

test('super-admin dapat memperbarui ekstrakurikuler', function () {
    $ekskul = Ekstrakurikuler::where('kode', 'FUTSAL')->firstOrFail();

    $this->actingAs(superAdminEkskul())->put(route('admin.ekstrakurikulers.update', $ekskul), [
        'kode' => 'FUTSAL',
        'nama' => 'Futsal Putra',
        'pembina_pilih' => '__luar__',
        'pembina_manual' => 'Coach Budi',
        'jadwal' => 'Rabu 16:00-18:00',
        'is_aktif' => '1',
    ])->assertRedirect(route('admin.ekstrakurikulers.index'));

    expect($ekskul->fresh()->nama)->toBe('Futsal Putra')
        ->and($ekskul->fresh()->pembina)->toBe('Coach Budi');
});

test('admin tanpa permission delete tidak dapat menghapus', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $ekskul = Ekstrakurikuler::where('kode', 'PMR')->firstOrFail();

    $this->actingAs($admin)->delete(route('admin.ekstrakurikulers.destroy', $ekskul))->assertForbidden();
    expect(Ekstrakurikuler::where('kode', 'PMR')->exists())->toBeTrue();
});

test('super-admin dapat menghapus ekstrakurikuler', function () {
    $ekskul = Ekstrakurikuler::where('kode', 'ROHIS')->firstOrFail();

    $this->actingAs(superAdminEkskul())->delete(route('admin.ekstrakurikulers.destroy', $ekskul))
        ->assertRedirect(route('admin.ekstrakurikulers.index'));
    expect(Ekstrakurikuler::where('kode', 'ROHIS')->exists())->toBeFalse();
});
