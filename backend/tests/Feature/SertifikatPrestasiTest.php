<?php

use App\Models\CalonSiswa;
use App\Models\Gelombang;
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
    Storage::fake('berkas');
});

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

/*
 * Jalur Prestasi Olahraga adalah satu-satunya jalur dengan
 * `wajib_sertifikat`, jadi test ini butuh jalur itu — bukan jalur mana pun
 * yang kebetulan terbuka.
 *
 * Data master hanya membuka Jalur Reguler dan Jalur Prestasi, jadi jalurnya
 * diaktifkan di sini. Yang diuji adalah berkas sertifikat, bukan
 * itu jalur mana yang default terbuka; membiarkannya bergantung pada
 * `PpdbSeeder` berarti test ikut gagal setiap kali daftar jalur default
 * berubah.
 */
if (! function_exists('jalurOlahraga')) {
    function jalurOlahraga(): JalurPendaftaran
    {
        $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Prestasi Olahraga')->firstOrFail();
        $jalur->update(['aktif' => true]);

        return $jalur;
    }
}

if (! function_exists('payloadPendaftaran')) {
    function payloadPendaftaran(int $jalurId, string $suffix, array $extra = []): array
    {
        return array_merge([
            'jalur_pendaftaran_id' => $jalurId,
            // Gelombang wajib dipilih selama ada gelombang yang jendelanya
            // terbuka. Diambil dari database karena seeder membuat tanggalnya
            // relatif terhadap hari ini.
            'gelombang_id' => Gelombang::terbuka(TahunAjaran::aktif()->first())->first()?->id,
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
    $jalur = jalurOlahraga();

    $response = $this->post(route('spmb.store'), payloadPendaftaran($jalur->id, '01'));

    $response->assertSessionHasErrors('sertifikat');
    expect(CalonSiswa::count())->toBe(0);
});

test('jalur olahraga dengan 2 sertifikat berhasil', function () {
    $jalur = jalurOlahraga();

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
    Storage::disk('berkas')->assertExists($calon->sertifikatPrestasis->first()->file_path);
});

test('jalur reguler tanpa sertifikat tetap lolos', function () {
    $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Reguler')->first();

    $response = $this->post(route('spmb.store'), payloadPendaftaran($jalur->id, '03'));

    $response->assertRedirect(route('spmb.pendaftaran'));
    $calon = CalonSiswa::where('nik', '9900000000000003')->first();
    expect($calon)->not->toBeNull()
        ->and($calon->sertifikatPrestasis)->toHaveCount(0);
});

test('jalur reguler dengan baris sertifikat kosong dari browser tetap lolos', function () {
    // Browser mengirim `sertifikat[0][nama]=""` walau seksi sertifikat
    // disembunyikan (jalur tidak mewajibkan). Baris kosong itu tidak boleh
    // memicu error validasi.
    $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Reguler')->first();

    $response = $this->post(route('spmb.store'), payloadPendaftaran($jalur->id, '06', [
        'sertifikat' => [['nama' => '']],
    ]));

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('spmb.pendaftaran'));
    expect(CalonSiswa::where('nik', '9900000000000006')->exists())->toBeTrue();
});

test('jalur reguler tetap bisa mengirim sertifikat bila diisi', function () {
    $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Reguler')->first();

    $response = $this->post(route('spmb.store'), payloadPendaftaran($jalur->id, '07', [
        'sertifikat' => [
            ['nama' => 'Juara 3 MTK', 'file' => UploadedFile::fake()->create('s.pdf', 100, 'application/pdf')],
        ],
    ]));

    $response->assertRedirect(route('spmb.pendaftaran'));
    $calon = CalonSiswa::where('nik', '9900000000000007')->first();
    expect($calon)->not->toBeNull()
        ->and($calon->sertifikatPrestasis)->toHaveCount(1);
});

test('baris sertifikat kosong pada jalur wajib tetap ditolak', function () {
    $jalur = jalurOlahraga();

    $response = $this->post(route('spmb.store'), payloadPendaftaran($jalur->id, '08', [
        'sertifikat' => [['nama' => '']],
    ]));

    $response->assertSessionHasErrors('sertifikat');
    expect(CalonSiswa::where('nik', '9900000000000008')->exists())->toBeFalse();
});

test('lebih dari 5 sertifikat ditolak', function () {
    $jalur = jalurOlahraga();
    $banyak = [];
    for ($i = 0; $i < 6; $i++) {
        $banyak[] = ['nama' => "Sertifikat {$i}", 'file' => UploadedFile::fake()->create("s{$i}.pdf", 100, 'application/pdf')];
    }

    $response = $this->post(route('spmb.store'), payloadPendaftaran($jalur->id, '04', ['sertifikat' => $banyak]));

    $response->assertSessionHasErrors('sertifikat');
});

test('file sertifikat selain pdf/jpg ditolak', function () {
    $jalur = jalurOlahraga();

    $response = $this->post(route('spmb.store'), payloadPendaftaran($jalur->id, '05', [
        'sertifikat' => [
            ['nama' => 'Dokumen Word', 'file' => UploadedFile::fake()->create('s.doc', 100, 'application/msword')],
        ],
    ]));

    $response->assertSessionHasErrors('sertifikat.0.file');
});

test('hapus calon menghapus file sertifikat', function () {
    $jalur = jalurOlahraga();

    $this->post(route('spmb.store'), payloadPendaftaran($jalur->id, '06', [
        'sertifikat' => [
            ['nama' => 'Juara 1 Renang', 'file' => UploadedFile::fake()->create('renang.pdf', 100, 'application/pdf')],
        ],
    ]))->assertRedirect(route('spmb.pendaftaran'));

    $calon = CalonSiswa::where('nik', '9900000000000006')->first();
    $path = $calon->sertifikatPrestasis->first()->file_path;
    Storage::disk('berkas')->assertExists($path);

    $this->actingAs(superAdmin())->delete(route('admin.calon-siswas.destroy', $calon))
        ->assertRedirect(route('admin.calon-siswas.index'));

    Storage::disk('berkas')->assertMissing($path);
    expect(CalonSiswa::find($calon->id))->toBeNull();
});

test('admin dapat menambah calon beserta sertifikat', function () {
    $jalur = jalurOlahraga();
    $tahun = TahunAjaran::aktif()->firstOrFail()->id;

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

test('admin ditolak menambah calon ke jalur wajib sertifikat tanpa sertifikat', function () {
    $jalur = jalurOlahraga();
    $tahun = TahunAjaran::aktif()->firstOrFail()->id;

    // Dulunya hanya JavaScript yang menahan; POST langsung tetap diterima.
    $response = $this->actingAs(superAdmin())->post(route('admin.calon-siswas.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => $tahun,
        'nama_lengkap' => 'Tanpa Sertifikat',
        'jenis_kelamin' => 'L',
        'nik' => '9900000000000008',
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Tanpa Sertifikat',
    ]);

    $response->assertSessionHasErrors('sertifikat');
    expect(CalonSiswa::where('nik', '9900000000000008')->exists())->toBeFalse();
});

test('admin boleh menambah calon ke jalur reguler tanpa sertifikat', function () {
    $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Reguler')->firstOrFail();
    $tahun = TahunAjaran::aktif()->firstOrFail()->id;

    $this->actingAs(superAdmin())->post(route('admin.calon-siswas.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => $tahun,
        'nama_lengkap' => 'Reguler Tanpa Sertifikat',
        'jenis_kelamin' => 'L',
        'nik' => '9900000000000009',
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Reguler',
    ])->assertSessionHasNoErrors();

    expect(CalonSiswa::where('nik', '9900000000000009')->exists())->toBeTrue();
});

test('edit calon jalur wajib yang sudah punya sertifikat tidak wajib upload ulang', function () {
    $jalur = jalurOlahraga();
    $calon = CalonSiswa::create([
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'no_pendaftaran' => 'PPDB-2026-2000',
        'nik' => '9900000000000010',
        'nama_lengkap' => 'Sudah Punya Sertifikat',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Lama',
        'status_pendaftaran' => 'menunggu',
    ]);
    $calon->sertifikatPrestasis()->create([
        'nama_sertifikat' => 'Juara 1',
        'file_path' => UploadedFile::fake()->create('s.pdf', 100, 'application/pdf')->store('berkas/sertifikat', 'berkas'),
    ]);

    // Admin hanya mau memperbaiki ketikan nama, tidak ada file baru dikirim.
    $this->actingAs(superAdmin())->put(route('admin.calon-siswas.update', $calon), [
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => $calon->tahun_ajaran_id,
        'nik' => $calon->nik,
        'nama_lengkap' => 'Nama Sudah Dikoreksi',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Baru',
    ])->assertSessionHasNoErrors();

    expect($calon->fresh())
        ->nama_lengkap->toBe('Nama Sudah Dikoreksi')
        ->and($calon->sertifikatPrestasis)->toHaveCount(1);
});
