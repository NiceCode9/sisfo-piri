<?php

use App\Models\CalonSiswa;
use App\Models\JalurPendaftaran;
use App\Models\PembayaranLainnya;
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

if (! function_exists('buatCalon')) {
    function buatCalon(array $overrides = []): CalonSiswa
    {
        static $n = 0;
        $n++;

        return CalonSiswa::create(array_merge([
            'jalur_pendaftaran_id' => JalurPendaftaran::first()->id,
            'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
            'no_pendaftaran' => sprintf('PPDB-2026-%04d', $n),
            'nik' => sprintf('990000000000%04d', $n),
            'nisn' => sprintf('990000%04d', $n),
            'nama_lengkap' => 'Calon Bayar '.$n,
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Sleman',
            'tanggal_lahir' => '2010-01-01',
            'agama' => 'Islam',
            'alamat' => 'Jl Bayar '.$n,
            'status_pendaftaran' => 'menunggu',
        ], $overrides));
    }
}

test('tamu tidak dapat membuka daftar pembayaran lainnya', function () {
    $this->get(route('admin.pembayaran-lainnyas.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka pembayaran lainnya', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.pembayaran-lainnyas.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar pembayaran lainnya', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.pembayaran-lainnyas.index'));
    $response->assertOk()->assertSee('Daftar Pembayaran Lainnya');
});

test('store tunai tanpa bukti langsung berhasil dengan kode LNN', function () {
    $calon = buatCalon();

    $response = $this->actingAs(superAdmin())->post(route('admin.pembayaran-lainnyas.store'), [
        'calon_siswa_id' => $calon->id,
        'nama_biaya' => 'Denda Keterlambatan',
        'jumlah' => 50000,
        'metode_pembayaran' => 'tunai',
    ]);

    $response->assertRedirect(route('admin.pembayaran-lainnyas.index'));
    $bayar = PembayaranLainnya::where('calon_siswa_id', $calon->id)->first();
    expect($bayar)->not->toBeNull()
        ->and($bayar->kode_pembayaran)->toStartWith('LNN-')
        ->and($bayar->status)->toBe('berhasil');
});

test('store transfer tanpa bukti ditolak validasi', function () {
    $calon = buatCalon();

    $response = $this->actingAs(superAdmin())->post(route('admin.pembayaran-lainnyas.store'), [
        'calon_siswa_id' => $calon->id,
        'nama_biaya' => 'Denda',
        'jumlah' => 50000,
        'metode_pembayaran' => 'transfer',
    ]);

    $response->assertSessionHasErrors('bukti_pembayaran_path');
    expect(PembayaranLainnya::where('calon_siswa_id', $calon->id)->exists())->toBeFalse();
});

test('store via modal show kembali ke show calon', function () {
    $calon = buatCalon();

    $response = $this->actingAs(superAdmin())->post(route('admin.pembayaran-lainnyas.store'), [
        'calon_siswa_id' => $calon->id,
        'nama_biaya' => 'Denda',
        'jumlah' => 50000,
        'metode_pembayaran' => 'tunai',
        'redirect_to' => route('admin.calon-siswas.show', $calon),
    ]);

    $response->assertRedirect(route('admin.calon-siswas.show', $calon));
});

test('update status menunggu ke berhasil', function () {
    $calon = buatCalon();
    $bayar = PembayaranLainnya::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'LNN-2026-0001',
        'nama_biaya' => 'Denda',
        'jumlah' => 50000,
        'metode_pembayaran' => 'tunai',
        'status' => 'menunggu',
    ]);

    $response = $this->actingAs(superAdmin())->patch(route('admin.pembayaran-lainnyas.status', $bayar), [
        'status' => 'berhasil',
    ]);

    $response->assertRedirect();
    expect($bayar->fresh()->status)->toBe('berhasil');
});

test('destroy menghapus baris dan file bukti', function () {
    $calon = buatCalon();
    $bayar = PembayaranLainnya::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'LNN-2026-0002',
        'nama_biaya' => 'Denda',
        'jumlah' => 50000,
        'metode_pembayaran' => 'transfer',
        'bukti_pembayaran_path' => UploadedFile::fake()->create('hapus.pdf', 100, 'application/pdf')->store('bukti', 'public'),
        'status' => 'berhasil',
    ]);
    $path = $bayar->bukti_pembayaran_path;

    $this->actingAs(superAdmin())->delete(route('admin.pembayaran-lainnyas.destroy', $bayar))
        ->assertRedirect(route('admin.pembayaran-lainnyas.index'));

    expect(PembayaranLainnya::find($bayar->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('admin tanpa permission delete tidak dapat menghapus', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $calon = buatCalon();
    $bayar = PembayaranLainnya::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'LNN-2026-0003',
        'nama_biaya' => 'Denda',
        'jumlah' => 50000,
        'metode_pembayaran' => 'tunai',
        'status' => 'berhasil',
    ]);

    $this->actingAs($admin)->delete(route('admin.pembayaran-lainnyas.destroy', $bayar))->assertForbidden();
    expect(PembayaranLainnya::find($bayar->id))->not->toBeNull();
});

test('export excel dan pdf mengikuti filter', function () {
    $calon = buatCalon();
    PembayaranLainnya::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'LNN-2026-0101',
        'nama_biaya' => 'Denda',
        'jumlah' => 50000,
        'metode_pembayaran' => 'tunai',
        'status' => 'berhasil',
        'tanggal_pembayaran' => '2026-06-01',
    ]);

    $excel = $this->actingAs(superAdmin())->get(route('admin.pembayaran-lainnyas.export.excel'));
    $excel->assertOk();
    expect($excel->headers->get('content-type'))->toContain('spreadsheetml');

    $pdf = $this->actingAs(superAdmin())->get(route('admin.pembayaran-lainnyas.export.pdf'));
    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf');
});

test('filter tanggal hanya kembalikan baris dalam rentang', function () {
    $calon = buatCalon();
    PembayaranLainnya::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'LNN-2026-0301',
        'nama_biaya' => 'Denda A',
        'jumlah' => 10000,
        'metode_pembayaran' => 'tunai',
        'status' => 'berhasil',
        'tanggal_pembayaran' => '2026-05-10',
    ]);
    PembayaranLainnya::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'LNN-2026-0302',
        'nama_biaya' => 'Denda B',
        'jumlah' => 20000,
        'metode_pembayaran' => 'tunai',
        'status' => 'berhasil',
        'tanggal_pembayaran' => '2026-07-20',
    ]);

    $response = $this->actingAs(superAdmin())->get(route('admin.pembayaran-lainnyas.index', [
        'tanggal_mulai' => '2026-07-01',
        'tanggal_sampai' => '2026-07-31',
    ]));

    $response->assertOk();
    $response->assertSee('LNN-2026-0302', false);
    $response->assertDontSee('LNN-2026-0301', false);
});
