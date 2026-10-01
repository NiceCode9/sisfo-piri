<?php

use App\Models\BerkasCalonSiswa;
use App\Models\CalonSiswa;
use App\Models\JalurPendaftaran;
use App\Models\Pembayaran;
use App\Models\PembayaranLainnya;
use App\Models\SertifikatPrestasi;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\WaliMurid;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    Storage::fake('berkas');
});

function calonDenganUser(): CalonSiswa
{
    $user = User::factory()->create();

    return CalonSiswa::create([
        'jalur_pendaftaran_id' => JalurPendaftaran::first()->id,
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'user_id' => $user->id,
        'no_pendaftaran' => 'PPDB-2026-0900',
        'nik' => '1234567800900',
        'nama_lengkap' => 'Dokumen Uji',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Ngaglik',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Dokumen',
        'status_pendaftaran' => 'menunggu',
    ]);
}

function adminWith(string $permission): User
{
    $user = User::factory()->create();
    $user->assignRole('super-admin');
    $user->givePermissionTo($permission);

    return $user;
}

test('berkas PPDB tidak lagi terekspos lewat symlink storage publik', function () {
    Storage::disk('berkas')->put('berkas/contoh.pdf', 'PDF PRIBAT');

    // Tidak boleh ada di disk publik, sehingga /storage/berkas/... selalu 404.
    expect(Storage::disk('public')->exists('berkas/contoh.pdf'))->toBeFalse();
});

test('calon siswa sendiri boleh melihat berkas pendaftarannya', function () {
    $calon = calonDenganUser();
    $berkas = $calon->berkasCalonSiswa()->create(['ijazah_path' => 'berkas/contoh.pdf']);
    Storage::disk('berkas')->put('berkas/contoh.pdf', 'PDF PRIBAT');

    $this->actingAs($calon->user)
        ->get(route('dokumen.berkas', [$berkas, 'ijazah_path']))
        ->assertOk();

    // Isi file benar-benar dialirkan, bukan sekadar 200 kosong.
    $this->assertSame(
        'PDF PRIBAT',
        $this->actingAs($calon->user)
            ->get(route('dokumen.berkas', [$berkas, 'ijazah_path']))
            ->streamedContent()
    );
});

test('orang lain tidak boleh melihat berkas pendaftaran calon', function () {
    $calon = calonDenganUser();
    $berkas = $calon->berkasCalonSiswa()->create(['ijazah_path' => 'berkas/contoh.pdf']);
    Storage::disk('berkas')->put('berkas/contoh.pdf', 'PDF PRIBAT');

    $this->actingAs(User::factory()->create())
        ->get(route('dokumen.berkas', [$berkas, 'ijazah_path']))
        ->assertForbidden();
});

test('wali murid boleh melihat berkas anak yang sudah diterima', function () {
    $calon = calonDenganUser();
    $calon->update(['status_pendaftaran' => 'diterima']);
    $siswa = Siswa::create([
        'calon_siswa_id' => $calon->id,
        'user_id' => $calon->user_id,
        'nisn' => '0098776655',
        'tahun_ajaran_id' => $calon->tahun_ajaran_id,
        'is_aktif' => true,
    ]);
    $wali = WaliMurid::create([
        'user_id' => User::factory()->create()->id,
        'siswa_id' => $siswa->id,
        'hubungan' => 'Ayah',
    ]);
    $berkas = $calon->berkasCalonSiswa()->create(['ijazah_path' => 'berkas/contoh.pdf']);
    Storage::disk('berkas')->put('berkas/contoh.pdf', 'PDF PRIBAT');

    $this->actingAs($wali->user)
        ->get(route('dokumen.berkas', [$berkas, 'ijazah_path']))
        ->assertOk();
});

test('admin dengan permission boleh melihat berkas calon lain', function () {
    $calon = calonDenganUser();
    $berkas = $calon->berkasCalonSiswa()->create(['ijazah_path' => 'berkas/contoh.pdf']);
    Storage::disk('berkas')->put('berkas/contoh.pdf', 'PDF PRIBAT');

    $this->actingAs(adminWith('berkas-calon-siswas.view'))
        ->get(route('dokumen.berkas', [$berkas, 'ijazah_path']))
        ->assertOk();
});

test('kolom berkas di luar daftar putih ditolak 404', function () {
    $calon = calonDenganUser();
    $berkas = $calon->berkasCalonSiswa()->create(['catatan_berkas' => 'rahasia', 'status_verifikasi' => true]);

    // `catatan_berkas` bukan kolom berkas — DokumenController harus menolak.
    $this->actingAs(adminWith('berkas-calon-siswas.view'))
        ->get(route('dokumen.berkas', [$berkas, 'catatan_berkas']))
        ->assertNotFound();
});

test('daftar label berkas hidup di satu konstanta model', function () {
    // Label dipakai bersama oleh form admin, dashboard siswa, dan pesan error.
    expect(BerkasCalonSiswa::UPLOADABLE)->toHaveCount(7)
        ->and(BerkasCalonSiswa::PERLU_PERBAIKAN)->toContain('sertifikat')
        ->and(BerkasCalonSiswa::LABELS)->toHaveKeys(BerkasCalonSiswa::PERLU_PERBAIKAN)
        ->and(BerkasCalonSiswa::LABELS['krm_path'])->toBe('Kartu Rencana Murid');

    // Setiap kolom yang boleh diunggah punya label; tidak ada nama kolom mentah.
    foreach (BerkasCalonSiswa::PERLU_PERBAIKAN as $field) {
        expect(BerkasCalonSiswa::LABELS[$field])->not->toEndWith('_path');
    }
});

test('berkas perlu perbaikan يترjemahkan kolom menjadi label ramah', function () {
    $calon = calonDenganUser();
    $berkas = $calon->berkasCalonSiswa()->create([
        'berkas_perlu_perbaikan' => ['ijazah_path', 'kk_path', 'sertifikat'],
    ]);

    expect($berkas->labelYangPerluPerbaikan())->toBe(['Ijazah', 'Kartu Keluarga', 'Sertifikat Prestasi'])
        ->and($berkas->perluPerbaikan())->toBeTrue()
        // `sertifikat` tidak bisa diunggah ulang lewat form berkas biasa.
        ->and($berkas->berkasPerluDiunggahUlang())->toBe(['ijazah_path', 'kk_path']);
});

test('sertifikat hanya untuk pemilik, wali, atau admin', function () {
    $calon = calonDenganUser();
    $sertifikat = SertifikatPrestasi::create([
        'calon_siswa_id' => $calon->id,
        'nama_sertifikat' => 'Juara 1',
        'file_path' => 'berkas/sertifikat/contoh.pdf',
    ]);
    Storage::disk('berkas')->put('berkas/sertifikat/contoh.pdf', 'SERTIFIKAT');

    $this->actingAs($calon->user)->get(route('dokumen.sertifikat', $sertifikat))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('dokumen.sertifikat', $sertifikat))->assertForbidden();
    $this->actingAs(adminWith('berkas-calon-siswas.view'))->get(route('dokumen.sertifikat', $sertifikat))->assertOk();
});

test('bukti pembayaran tidak dapat diakses tanpa izin', function () {
    $calon = calonDenganUser();
    $pembayaran = Pembayaran::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'PAY-2026-0900',
        'jumlah' => 100000,
        'metode_pembayaran' => 'transfer',
        'jenis_pembayaran' => 'penuh',
        'status' => 'berhasil',
        'bukti_pembayaran_path' => 'bukti/contoh.png',
    ]);
    Storage::disk('berkas')->put('bukti/contoh.png', 'BUKTI');

    $this->actingAs(User::factory()->create())->get(route('dokumen.pembayaran', $pembayaran))->assertForbidden();
    $this->actingAs($calon->user)->get(route('dokumen.pembayaran', $pembayaran))->assertOk();
    $this->actingAs(adminWith('pembayarans.view'))->get(route('dokumen.pembayaran', $pembayaran))->assertOk();
});

test('bukti pembayaran lainnya mengikuti aturan izin yang sama', function () {
    $calon = calonDenganUser();
    $pembayaran = PembayaranLainnya::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'LNN-2026-0900',
        'nama_biaya' => 'Seragam',
        'jumlah' => 150000,
        'metode_pembayaran' => 'tunai',
        'status' => 'berhasil',
        'bukti_pembayaran_path' => 'bukti/lain.png',
    ]);
    Storage::disk('berkas')->put('bukti/lain.png', 'BUKTI LAIN');

    $this->actingAs(User::factory()->create())->get(route('dokumen.pembayaran-lainnya', $pembayaran))->assertForbidden();
    $this->actingAs($calon->user)->get(route('dokumen.pembayaran-lainnya', $pembayaran))->assertOk();
    $this->actingAs(adminWith('pembayaran-lainnyas.view'))->get(route('dokumen.pembayaran-lainnya', $pembayaran))->assertOk();
});

test('berkas tanpa file di database menghasilkan 404', function () {
    $calon = calonDenganUser();
    $berkas = BerkasCalonSiswa::create(['calon_siswa_id' => $calon->id]);

    $this->actingAs(adminWith('berkas-calon-siswas.view'))
        ->get(route('dokumen.berkas', [$berkas, 'ijazah_path']))
        ->assertNotFound();
});

test('berkas hilang di penyimpanan menghasilkan 404, bukan uncaught error', function () {
    $calon = calonDenganUser();
    $berkas = $calon->berkasCalonSiswa()->create(['ijazah_path' => 'berkas/hilang.pdf']);

    $this->actingAs(adminWith('berkas-calon-siswas.view'))
        ->get(route('dokumen.berkas', [$berkas, 'ijazah_path']))
        ->assertNotFound();
});

test('pengunjung tanpa login diarahkan ke halaman login', function () {
    $calon = calonDenganUser();
    $berkas = $calon->berkasCalonSiswa()->create(['ijazah_path' => 'berkas/contoh.pdf']);

    $this->get(route('dokumen.berkas', [$berkas, 'ijazah_path']))
        ->assertRedirect(route('login'));
});
