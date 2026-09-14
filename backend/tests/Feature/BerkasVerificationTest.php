<?php

use App\Models\CalonSiswa;
use App\Models\JalurPendaftaran;
use App\Models\TahunAjaran;
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

function createCalonWithBerkas(array $overrides = []): CalonSiswa
{
    $tahun = TahunAjaran::aktif()->first();
    $jalur = JalurPendaftaran::first();

    $calon = CalonSiswa::create(array_merge([
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => $tahun->id,
        'no_pendaftaran' => 'PPDB-2026-0001',
        'nik' => '1234567890123456',
        'nama_lengkap' => 'Berkas Test',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Test',
        'status_pendaftaran' => 'menunggu',
    ], $overrides));

    // initial berkas file
    $calon->berkasCalonSiswa()->create([
        'ijazah_path' => UploadedFile::fake()->create('old-ijazah.pdf', 100, 'application/pdf')->store('berkas', 'public'),
    ]);

    return $calon->fresh();
}

test('user tanpa permission ditolak verifikasi berkas', function () {
    $calon = createCalonWithBerkas();
    $user = User::factory()->create();
    $this->actingAs($user)->patch(route('admin.calon-siswas.berkas', $calon), [
        'status_verifikasi' => 1,
    ])->assertForbidden();
});

test('super-admin dapat verifikasi berkas dengan flag', function () {
    $calon = createCalonWithBerkas();

    $response = $this->actingAs(superAdmin())->patch(route('admin.calon-siswas.berkas', $calon), [
        'status_verifikasi' => 1,
        'berkas_perlu_perbaikan' => ['kk_path'],
        'alasan_penolakan' => 'KK buram',
        'catatan_berkas' => 'Harap upload ulang',
    ]);

    $response->assertRedirect();
    $berkas = $calon->fresh()->berkasCalonSiswa;
    expect((bool) $berkas->status_verifikasi)->toBeTrue()
        ->and($berkas->berkas_perlu_perbaikan)->toBe(['kk_path'])
        ->and($berkas->alasan_penolakan)->toBe('KK buram');
});

test('super-admin dapat ganti file berkas dan old file terhapus', function () {
    $calon = createCalonWithBerkas();
    $oldPath = $calon->berkasCalonSiswa->ijazah_path;
    Storage::disk('public')->assertExists($oldPath);

    $newFile = UploadedFile::fake()->create('new-ijazah.pdf', 100, 'application/pdf');

    $response = $this->actingAs(superAdmin())->patch(route('admin.calon-siswas.berkas', $calon), [
        'status_verifikasi' => 1,
        'ijazah_path' => $newFile,
    ]);

    $response->assertRedirect();
    Storage::disk('public')->assertMissing($oldPath);
    $fresh = $calon->fresh()->berkasCalonSiswa;
    expect($fresh->ijazah_path)->not->toBe($oldPath);
    Storage::disk('public')->assertExists($fresh->ijazah_path);
});

test('validasi foto mimes ditolak', function () {
    $calon = createCalonWithBerkas();

    $response = $this->actingAs(superAdmin())->patch(route('admin.calon-siswas.berkas', $calon), [
        'status_verifikasi' => 1,
        'foto_path' => UploadedFile::fake()->create('foto.pdf', 100, 'application/pdf'),
    ]);

    $response->assertSessionHasErrors('foto_path');
});

test('tamu tidak dapat verifikasi berkas', function () {
    $calon = createCalonWithBerkas();
    $this->patch(route('admin.calon-siswas.berkas', $calon), [
        'status_verifikasi' => 1,
    ])->assertRedirect(route('login'));
});
