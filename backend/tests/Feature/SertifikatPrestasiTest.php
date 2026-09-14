<?php

use App\Models\CalonSiswa;
use App\Models\JalurPendaftaran;
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

if (! function_exists('payloadPendaftaran')) {
    function payloadPendaftaran(int $jalurId, string $suffix, array $extra = []): array
    {
        return array_merge([
            'jalur_pendaftaran_id' => $jalurId,
            'nama_lengkap' => 'Siswa Sertifikat',
            'jenis_kelamin' => 'L',
            'nik' => '99000000000000'.$suffix,
            'nisn' => '99000000'.$suffix,
            'tempat_lahir' => 'Sleman',
            'tanggal_lahir' => '2010-05-10',
            'agama' => 'Islam',
            'asal_sekolah' => 'SMP 1',
            'alamat' => 'Jl Sertifikat 123',
            'no_hp' => '081234567890',
            'email' => "sertifikat{$suffix}@example.com",
            'nama_ayah' => 'Ayah',
            'pekerjaan_ayah' => 'Petani',
            'nama_ibu' => 'Ibu',
            'pekerjaan_ibu' => 'IRT',
            'no_hp_orang_tua' => '081234567891',
            'ijazah_path' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
            'kk_path' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
            'akta_path' => UploadedFile::fake()->create('akta.pdf', 100, 'application/pdf'),
            'foto_path' => UploadedFile::fake()->image('foto.jpg'),
            'skl_path' => UploadedFile::fake()->create('skl.pdf', 100, 'application/pdf'),
        ], $extra);
    }
}

test('jalur olahraga tanpa sertifikat ditolak', function () {
    $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Prestasi Olahraga')->first();

    $response = $this->post(route('spmb.store'), payloadPendaftaran($jalur->id, '01'));

    $response->assertSessionHasErrors('sertifikat');
    expect(CalonSiswa::count())->toBe(0);
});

test('jalur olahraga dengan 2 sertifikat berhasil', function () {
    $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Prestasi Olahraga')->first();

    $response = $this->post(route('spmb.store'), payloadPendaftaran($jalur->id, '02', [
        'sertifikat' => [
            ['nama' => 'Juara 1 Pencak Silat Provinsi', 'file' => UploadedFile::fake()->create('s1.pdf', 100, 'application/pdf')],
            ['nama' => 'Juara 2 Atletik Kabupaten', 'file' => UploadedFile::fake()->image('s2.jpg')],
        ],
    ]));

    $response->assertRedirect(route('spmb.pendaftaran'));
    $calon = CalonSiswa::where('nik', '9900000000000002')->first();
    expect($calon)->not->toBeNull()
        ->and($calon->sertifikatPrestasis)->toHaveCount(2);
    expect($calon->sertifikatPrestasis->pluck('nama_sertifikat')->all())
        ->toContain('Juara 1 Pencak Silat Provinsi');
    Storage::disk('public')->assertExists($calon->sertifikatPrestasis->first()->file_path);
});

test('jalur reguler tanpa sertifikat tetap lolos', function () {
    $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Reguler')->first();

    $response = $this->post(route('spmb.store'), payloadPendaftaran($jalur->id, '03'));

    $response->assertRedirect(route('spmb.pendaftaran'));
    $calon = CalonSiswa::where('nik', '9900000000000003')->first();
    expect($calon)->not->toBeNull()
        ->and($calon->sertifikatPrestasis)->toHaveCount(0);
});

test('lebih dari 5 sertifikat ditolak', function () {
    $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Prestasi Olahraga')->first();
    $banyak = [];
    for ($i = 0; $i < 6; $i++) {
        $banyak[] = ['nama' => "Sertifikat {$i}", 'file' => UploadedFile::fake()->create("s{$i}.pdf", 100, 'application/pdf')];
    }

    $response = $this->post(route('spmb.store'), payloadPendaftaran($jalur->id, '04', ['sertifikat' => $banyak]));

    $response->assertSessionHasErrors('sertifikat');
});

test('file sertifikat selain pdf/jpg ditolak', function () {
    $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Prestasi Olahraga')->first();

    $response = $this->post(route('spmb.store'), payloadPendaftaran($jalur->id, '05', [
        'sertifikat' => [
            ['nama' => 'Dokumen Word', 'file' => UploadedFile::fake()->create('s.doc', 100, 'application/msword')],
        ],
    ]));

    $response->assertSessionHasErrors('sertifikat.0.file');
});

test('hapus calon menghapus file sertifikat', function () {
    $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Prestasi Olahraga')->first();

    $this->post(route('spmb.store'), payloadPendaftaran($jalur->id, '06', [
        'sertifikat' => [
            ['nama' => 'Juara 1 Renang', 'file' => UploadedFile::fake()->create('renang.pdf', 100, 'application/pdf')],
        ],
    ]))->assertRedirect(route('spmb.pendaftaran'));

    $calon = CalonSiswa::where('nik', '9900000000000006')->first();
    $path = $calon->sertifikatPrestasis->first()->file_path;
    Storage::disk('public')->assertExists($path);

    $this->actingAs(superAdmin())->delete(route('admin.calon-siswas.destroy', $calon))
        ->assertRedirect(route('admin.calon-siswas.index'));

    Storage::disk('public')->assertMissing($path);
    expect(CalonSiswa::find($calon->id))->toBeNull();
});

test('admin dapat menambah calon beserta sertifikat', function () {
    $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Prestasi Olahraga')->first();
    $tahun = $jalur->kuotaPendaftaran->first()->tahun_ajaran_id;

    $response = $this->actingAs(superAdmin())->post(route('admin.calon-siswas.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => $tahun,
        'nama_lengkap' => 'Calon Admin',
        'jenis_kelamin' => 'P',
        'nik' => '9900000000000007',
        'nisn' => '9900000007',
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'asal_sekolah' => 'SMP 2',
        'alamat' => 'Jl Admin 1',
        'no_hp' => '081234567890',
        'email' => 'calonadmin@example.com',
        'nama_ayah' => 'Ayah',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu',
        'pekerjaan_ibu' => 'IRT',
        'no_hp_orang_tua' => '081234567891',
        'sertifikat' => [
            ['nama' => 'Juara 1 Bulutangkis', 'file' => UploadedFile::fake()->create('bulu.pdf', 100, 'application/pdf')],
        ],
    ]);

    $response->assertRedirect(route('admin.calon-siswas.index'));
    $calon = CalonSiswa::where('nik', '9900000000000007')->first();
    expect($calon)->not->toBeNull()
        ->and($calon->sertifikatPrestasis)->toHaveCount(1);
});
