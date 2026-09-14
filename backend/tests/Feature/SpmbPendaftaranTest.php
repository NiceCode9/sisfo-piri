<?php

use App\Models\CalonSiswa;
use App\Models\JalurPendaftaran;
use App\Models\KuotaPendaftaran;
use App\Models\TahunAjaran;
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

test('halaman pendaftaran dapat ditampilkan', function () {
    $response = $this->get(route('spmb.pendaftaran'));
    $response->assertOk()->assertSee('Pendaftaran');
});

test('pendaftaran publik sukses dengan 5 berkas', function () {
    $jalur = JalurPendaftaran::first();

    $response = $this->post(route('spmb.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'nama_lengkap' => 'Siswa Publik',
        'jenis_kelamin' => 'P',
        'nik' => '1234567890123456',
        'nisn' => '1234567890',
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-05-10',
        'agama' => 'Islam',
        'asal_sekolah' => 'SMP 1',
        'alamat' => 'Jl Publik 123',
        'no_hp' => '081234567890',
        'email' => 'publik@example.com',
        'nama_ayah' => 'Ayah Publik',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu Publik',
        'pekerjaan_ibu' => 'IRT',
        'no_hp_orang_tua' => '081234567891',
        'ijazah_path' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
        'kk_path' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
        'akta_path' => UploadedFile::fake()->create('akta.pdf', 100, 'application/pdf'),
        'foto_path' => UploadedFile::fake()->image('foto.jpg'),
        'skl_path' => UploadedFile::fake()->create('skl.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('spmb.pendaftaran'));
    $response->assertSessionHas('success');
    $calon = CalonSiswa::where('nik', '1234567890123456')->first();
    expect($calon)->not->toBeNull()
        ->and($calon->no_pendaftaran)->toStartWith('PPDB-');
    expect($calon->berkasCalonSiswa)->not->toBeNull();
    Storage::disk('public')->assertExists($calon->berkasCalonSiswa->ijazah_path);
});

test('validasi nik 16 digit ditolak', function () {
    $jalur = JalurPendaftaran::first();

    $response = $this->post(route('spmb.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'nama_lengkap' => 'Test',
        'jenis_kelamin' => 'L',
        'nik' => '123',
        'nisn' => '1234567890',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'asal_sekolah' => 'SMP Test',
        'alamat' => 'Jl Test',
        'no_hp' => '081234567890',
        'email' => 'test@example.com',
        'nama_ayah' => 'Ayah',
        'pekerjaan_ayah' => 'Wiraswasta',
        'nama_ibu' => 'Ibu',
        'pekerjaan_ibu' => 'IRT',
        'no_hp_orang_tua' => '081234567891',
        'ijazah_path' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
        'kk_path' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
        'akta_path' => UploadedFile::fake()->create('akta.pdf', 100, 'application/pdf'),
        'foto_path' => UploadedFile::fake()->image('foto.jpg'),
        'skl_path' => UploadedFile::fake()->create('skl.pdf', 100, 'application/pdf'),
    ]);

    $response->assertSessionHasErrors('nik');
});

test('validasi 5 berkas wajib ditolak jika kosong', function () {
    $jalur = JalurPendaftaran::first();

    $response = $this->post(route('spmb.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'nama_lengkap' => 'Test',
        'jenis_kelamin' => 'L',
        'nik' => '1234567890123456',
        'nisn' => '1234567890',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'asal_sekolah' => 'SMP Test',
        'alamat' => 'Jl Test',
        'no_hp' => '081234567890',
        'email' => 'test@example.com',
        'nama_ayah' => 'Ayah',
        'pekerjaan_ayah' => 'Wiraswasta',
        'nama_ibu' => 'Ibu',
        'pekerjaan_ibu' => 'IRT',
        'no_hp_orang_tua' => '081234567891',
    ]);

    $response->assertSessionHasErrors(['ijazah_path', 'kk_path', 'akta_path', 'foto_path', 'skl_path']);
});

test('kuota penuh ditolak di pendaftaran publik', function () {
    $tahun = TahunAjaran::aktif()->first();
    $jalur = JalurPendaftaran::first();
    $kuota = KuotaPendaftaran::where('tahun_ajaran_id', $tahun->id)->where('jalur_pendaftaran_id', $jalur->id)->first();
    $kuota->update(['kuota' => 1, 'terisi' => 1]);

    $response = $this->post(route('spmb.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'nama_lengkap' => 'Kuota Penuh',
        'jenis_kelamin' => 'L',
        'nik' => '1234567890123456',
        'nisn' => '1234567890',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'asal_sekolah' => 'SMP Test',
        'alamat' => 'Jl Test',
        'no_hp' => '081234567890',
        'email' => 'penuh@example.com',
        'nama_ayah' => 'Ayah',
        'pekerjaan_ayah' => 'Wiraswasta',
        'nama_ibu' => 'Ibu',
        'pekerjaan_ibu' => 'IRT',
        'no_hp_orang_tua' => '081234567891',
        'ijazah_path' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
        'kk_path' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
        'akta_path' => UploadedFile::fake()->create('akta.pdf', 100, 'application/pdf'),
        'foto_path' => UploadedFile::fake()->image('foto.jpg'),
        'skl_path' => UploadedFile::fake()->create('skl.pdf', 100, 'application/pdf'),
    ]);

    $response->assertSessionHasErrors(['jalur_pendaftaran_id']);
});

test('tahun ajaran aktif tidak ada ditolak', function () {
    TahunAjaran::query()->update(['status_aktif' => false]);

    $jalur = JalurPendaftaran::first();

    $response = $this->post(route('spmb.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'nama_lengkap' => 'Test',
        'jenis_kelamin' => 'L',
        'nik' => '1234567890123456',
        'nisn' => '1234567890',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'asal_sekolah' => 'SMP Test',
        'alamat' => 'Jl Test',
        'no_hp' => '081234567890',
        'email' => 'test2@example.com',
        'nama_ayah' => 'Ayah',
        'pekerjaan_ayah' => 'Wiraswasta',
        'nama_ibu' => 'Ibu',
        'pekerjaan_ibu' => 'IRT',
        'no_hp_orang_tua' => '081234567891',
        'ijazah_path' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
        'kk_path' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
        'akta_path' => UploadedFile::fake()->create('akta.pdf', 100, 'application/pdf'),
        'foto_path' => UploadedFile::fake()->image('foto.jpg'),
        'skl_path' => UploadedFile::fake()->create('skl.pdf', 100, 'application/pdf'),
    ]);

    $response->assertSessionHasErrors(['jalur_pendaftaran_id']);
});
