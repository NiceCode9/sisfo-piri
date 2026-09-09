<?php

use App\Models\CalonSiswa;
use App\Models\JalurPendaftaran;
use App\Models\KuotaPendaftaran;
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

test('tamu tidak dapat membuka daftar calon siswa', function () {
    $this->get(route('admin.calon-siswas.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka daftar calon siswa', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.calon-siswas.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar calon siswa', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.calon-siswas.index'));
    $response->assertOk()->assertSee('Daftar Calon Siswa');
});

test('halaman show tampil gaya modern lengkap', function () {
    $calon = CalonSiswa::create([
        'jalur_pendaftaran_id' => JalurPendaftaran::first()->id,
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'no_pendaftaran' => 'PPDB-2026-0001',
        'nik' => '1234567890123456',
        'nama_lengkap' => 'Show Test',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Test',
        'status_pendaftaran' => 'menunggu',
    ]);

    $response = $this->actingAs(superAdmin())->get(route('admin.calon-siswas.show', $calon));

    $response->assertOk();
    foreach (['header-section', 'document-list', 'payment-history', 'modalPembayaran', 'form-status', 'Rincian Biaya'] as $marker) {
        $response->assertSee($marker, false);
    }
});

test('super-admin dapat menambah calon siswa beserta berkas', function () {
    $jalur = JalurPendaftaran::first();

    $response = $this->actingAs(superAdmin())->post(route('admin.calon-siswas.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'nik' => '1234567890123456',
        'nisn' => '1234567890',
        'nama_lengkap' => 'Budi Test',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Ngaglik',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Test 123',
        'no_hp' => '081234567890',
        'email' => 'budi@example.com',
        'asal_sekolah' => 'SMP Test',
        'nama_ayah' => 'Ayah Budi',
        'pekerjaan_ayah' => 'Wiraswasta',
        'nama_ibu' => 'Ibu Budi',
        'pekerjaan_ibu' => 'IRT',
        'no_hp_orang_tua' => '081234567891',
        'ijazah_path' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
        'kk_path' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
        'akta_path' => UploadedFile::fake()->create('akta.pdf', 100, 'application/pdf'),
        'foto_path' => UploadedFile::fake()->image('foto.jpg'),
        'skl_path' => UploadedFile::fake()->create('skl.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('admin.calon-siswas.index'));
    $calon = CalonSiswa::where('nik', '1234567890123456')->first();
    expect($calon)->not->toBeNull()
        ->and($calon->berkasCalonSiswa)->not->toBeNull()
        ->and($calon->berkasCalonSiswa->ijazah_path)->not->toBeNull();
    Storage::disk('public')->assertExists($calon->berkasCalonSiswa->ijazah_path);
    expect($calon->logStatusPendaftaran()->count())->toBe(1);
});

test('nik duplikat ditolak saat tambah calon', function () {
    $jalur = JalurPendaftaran::first();
    CalonSiswa::create([
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'no_pendaftaran' => 'PPDB-2026-0001',
        'nik' => '1234567890123456',
        'nama_lengkap' => 'Existing',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Test',
        'status_pendaftaran' => 'menunggu',
    ]);

    $response = $this->actingAs(superAdmin())->post(route('admin.calon-siswas.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'nik' => '1234567890123456',
        'nama_lengkap' => 'Duplikat',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Test',
    ]);

    $response->assertSessionHasErrors('nik');
});

test('kuota penuh ditolak saat tambah calon', function () {
    $tahun = TahunAjaran::aktif()->first();
    $jalur = JalurPendaftaran::first();
    $kuota = KuotaPendaftaran::where('tahun_ajaran_id', $tahun->id)->where('jalur_pendaftaran_id', $jalur->id)->first();
    $kuota->update(['kuota' => 1, 'terisi' => 1]);

    $response = $this->actingAs(superAdmin())->post(route('admin.calon-siswas.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'nik' => '1234567890123457',
        'nama_lengkap' => 'Penuh Test',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Test',
    ]);

    $response->assertSessionHas('error');
});

test('super-admin dapat ubah status menunggu ke diterima dan terisi increment', function () {
    $tahun = TahunAjaran::aktif()->first();
    $jalur = JalurPendaftaran::first();
    $kuota = KuotaPendaftaran::where('tahun_ajaran_id', $tahun->id)->where('jalur_pendaftaran_id', $jalur->id)->first();
    $kuota->update(['kuota' => 5, 'terisi' => 0]);

    $calon = CalonSiswa::create([
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => $tahun->id,
        'no_pendaftaran' => 'PPDB-2026-0001',
        'nik' => '1234567890123456',
        'nama_lengkap' => 'Status Test',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Test',
        'status_pendaftaran' => 'menunggu',
    ]);

    $response = $this->actingAs(superAdmin())->patch(route('admin.calon-siswas.status', $calon), [
        'status' => 'diterima',
        'catatan' => 'Lolos',
    ]);

    $response->assertRedirect();
    expect($calon->fresh()->status_pendaftaran)->toBe('diterima')
        ->and($kuota->fresh()->terisi)->toBe(1);
});

test('status sama ditolak', function () {
    $tahun = TahunAjaran::aktif()->first();
    $jalur = JalurPendaftaran::first();
    $calon = CalonSiswa::create([
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => $tahun->id,
        'no_pendaftaran' => 'PPDB-2026-0001',
        'nik' => '1234567890123456',
        'nama_lengkap' => 'Status Test',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Test',
        'status_pendaftaran' => 'menunggu',
    ]);

    $response = $this->actingAs(superAdmin())->patch(route('admin.calon-siswas.status', $calon), [
        'status' => 'menunggu',
    ]);

    $response->assertSessionHas('error');
});

test('diterima ke ditolak decrement terisi', function () {
    $tahun = TahunAjaran::aktif()->first();
    $jalur = JalurPendaftaran::first();
    $kuota = KuotaPendaftaran::where('tahun_ajaran_id', $tahun->id)->where('jalur_pendaftaran_id', $jalur->id)->first();
    $kuota->update(['kuota' => 5, 'terisi' => 1]);

    $calon = CalonSiswa::create([
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => $tahun->id,
        'no_pendaftaran' => 'PPDB-2026-0001',
        'nik' => '1234567890123456',
        'nama_lengkap' => 'Status Test',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Test',
        'status_pendaftaran' => 'diterima',
    ]);

    $this->actingAs(superAdmin())->patch(route('admin.calon-siswas.status', $calon), [
        'status' => 'ditolak',
    ]);

    expect($kuota->fresh()->terisi)->toBe(0);
});

test('admin tanpa permission delete tidak dapat menghapus calon', function () {
    $tahun = TahunAjaran::aktif()->first();
    $jalur = JalurPendaftaran::first();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $calon = CalonSiswa::create([
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => $tahun->id,
        'no_pendaftaran' => 'PPDB-2026-0001',
        'nik' => '1234567890123456',
        'nama_lengkap' => 'Hapus Test',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Test',
        'status_pendaftaran' => 'menunggu',
    ]);

    $this->actingAs($admin)->delete(route('admin.calon-siswas.destroy', $calon))->assertForbidden();
    expect(CalonSiswa::find($calon->id))->not->toBeNull();
});
