<?php

use App\Models\BiayaPendaftaran;
use App\Models\CalonSiswa;
use App\Models\JalurPendaftaran;
use App\Models\Pembayaran;
use App\Models\RencanaAngsuran;
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

test('tamu tidak dapat membuka daftar pembayaran', function () {
    $this->get(route('admin.pembayarans.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka pembayaran', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.pembayarans.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar pembayaran', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.pembayarans.index'));
    $response->assertOk()->assertSee('Daftar Pembayaran');
});

test('halaman show tagihan dapat ditampilkan', function () {
    $calon = buatCalon();
    $biaya = BiayaPendaftaran::where('jenis_biaya', 'Uang Pangkal')->first();
    $bayar = Pembayaran::create([
        'calon_siswa_id' => $calon->id,
        'biaya_pendaftaran_id' => $biaya->id,
        'kode_pembayaran' => 'PAY-2026-0099',
        'jumlah' => $biaya->jumlah,
        'metode_pembayaran' => 'tunai',
        'jenis_pembayaran' => 'penuh',
        'status' => 'menunggu',
    ]);

    $response = $this->actingAs(superAdmin())->get(route('admin.pembayarans.show', $bayar));

    $response->assertOk();
    foreach (['Ubah Status', 'Buat Rencana Angsuran', 'PAY-2026-0099'] as $marker) {
        $response->assertSee($marker, false);
    }
});

test('store manual tunai tanpa bukti langsung berhasil dengan kode PAY', function () {
    $calon = buatCalon();

    $response = $this->actingAs(superAdmin())->post(route('admin.pembayarans.store'), [
        'calon_siswa_id' => $calon->id,
        'jumlah' => 100000,
        'metode_pembayaran' => 'tunai',
    ]);

    $response->assertRedirect(route('admin.pembayarans.index'));
    $bayar = Pembayaran::where('calon_siswa_id', $calon->id)->first();
    expect($bayar)->not->toBeNull()
        ->and($bayar->kode_pembayaran)->toStartWith('PAY-')
        ->and($bayar->status)->toBe('berhasil');
});

test('store transfer tanpa bukti ditolak validasi', function () {
    $calon = buatCalon();

    $response = $this->actingAs(superAdmin())->post(route('admin.pembayarans.store'), [
        'calon_siswa_id' => $calon->id,
        'jumlah' => 100000,
        'metode_pembayaran' => 'transfer',
    ]);

    $response->assertSessionHasErrors('bukti_pembayaran_path');
    expect(Pembayaran::where('calon_siswa_id', $calon->id)->exists())->toBeFalse();
});

test('store dengan bukti otomatis berhasil dan tanggal hari ini', function () {
    $calon = buatCalon();

    $response = $this->actingAs(superAdmin())->post(route('admin.pembayarans.store'), [
        'calon_siswa_id' => $calon->id,
        'jumlah' => 100000,
        'metode_pembayaran' => 'transfer',
        'bukti_pembayaran_path' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('admin.pembayarans.index'));
    $bayar = Pembayaran::where('calon_siswa_id', $calon->id)->first();
    expect($bayar->status)->toBe('berhasil')
        ->and($bayar->tanggal_pembayaran)->toBe(now()->toDateString());
    Storage::disk('public')->assertExists($bayar->bukti_pembayaran_path);
});

test('validasi menolak metode salah dan jumlah negatif', function () {
    $calon = buatCalon();

    $response = $this->actingAs(superAdmin())->post(route('admin.pembayarans.store'), [
        'calon_siswa_id' => $calon->id,
        'jumlah' => -5000,
        'metode_pembayaran' => 'ovo',
    ]);

    $response->assertSessionHasErrors(['jumlah', 'metode_pembayaran']);
});

test('update status menunggu ke berhasil', function () {
    $calon = buatCalon();
    $bayar = Pembayaran::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'PAY-2026-0001',
        'jumlah' => 100000,
        'metode_pembayaran' => 'tunai',
        'jenis_pembayaran' => 'penuh',
        'status' => 'menunggu',
    ]);

    $response = $this->actingAs(superAdmin())->patch(route('admin.pembayarans.status', $bayar), [
        'status' => 'berhasil',
    ]);

    $response->assertRedirect();
    expect($bayar->fresh()->status)->toBe('berhasil');
});

test('ganti bukti menghapus file lama', function () {
    $calon = buatCalon();
    $bayar = Pembayaran::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'PAY-2026-0002',
        'jumlah' => 100000,
        'metode_pembayaran' => 'transfer',
        'jenis_pembayaran' => 'penuh',
        'bukti_pembayaran_path' => UploadedFile::fake()->create('lama.pdf', 100, 'application/pdf')->store('bukti', 'public'),
        'status' => 'menunggu',
    ]);
    $lama = $bayar->bukti_pembayaran_path;
    Storage::disk('public')->assertExists($lama);

    $this->actingAs(superAdmin())->put(route('admin.pembayarans.update', $bayar), [
        'calon_siswa_id' => $calon->id,
        'jumlah' => 100000,
        'metode_pembayaran' => 'transfer',
        'bukti_pembayaran_path' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
    ])->assertRedirect(route('admin.pembayarans.index'));

    Storage::disk('public')->assertMissing($lama);
    Storage::disk('public')->assertExists($bayar->fresh()->bukti_pembayaran_path);
});

test('destroy menghapus baris dan file bukti', function () {
    $calon = buatCalon();
    $bayar = Pembayaran::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'PAY-2026-0003',
        'jumlah' => 100000,
        'metode_pembayaran' => 'tunai',
        'jenis_pembayaran' => 'penuh',
        'bukti_pembayaran_path' => UploadedFile::fake()->create('hapus.pdf', 100, 'application/pdf')->store('bukti', 'public'),
        'status' => 'menunggu',
    ]);
    $path = $bayar->bukti_pembayaran_path;

    $this->actingAs(superAdmin())->delete(route('admin.pembayarans.destroy', $bayar))
        ->assertRedirect(route('admin.pembayarans.index'));

    expect(Pembayaran::find($bayar->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('admin tanpa permission delete tidak dapat menghapus', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $calon = buatCalon();
    $bayar = Pembayaran::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'PAY-2026-0004',
        'jumlah' => 100000,
        'metode_pembayaran' => 'tunai',
        'jenis_pembayaran' => 'penuh',
        'status' => 'menunggu',
    ]);

    $this->actingAs($admin)->delete(route('admin.pembayarans.destroy', $bayar))->assertForbidden();
    expect(Pembayaran::find($bayar->id))->not->toBeNull();
});

test('status diterima auto-create 4 tagihan wajib', function () {
    $user = User::factory()->create();
    $calon = buatCalon(['user_id' => $user->id]);

    $this->actingAs(superAdmin())->patch(route('admin.calon-siswas.status', $calon), [
        'status' => 'diterima',
    ])->assertRedirect();

    $tagihan = Pembayaran::where('calon_siswa_id', $calon->id)->get();
    $wajib = BiayaPendaftaran::where('tahun_ajaran_id', $calon->tahun_ajaran_id)->where('wajib_bayar', true)->get();

    expect($tagihan)->toHaveCount($wajib->count())
        ->and($wajib->count())->toBe(4);

    foreach ($tagihan as $t) {
        expect($t->status)->toBe('menunggu')
            ->and($t->kode_pembayaran)->toStartWith('PAY-')
            ->and((float) $t->jumlah)->toBe((float) $t->biayaPendaftaran->jumlah);
    }
});

test('create hanya menampilkan calon belum lunas', function () {
    $belum = buatCalon();
    Pembayaran::create([
        'calon_siswa_id' => $belum->id,
        'kode_pembayaran' => 'PAY-2026-0101',
        'jumlah' => 100000,
        'metode_pembayaran' => 'tunai',
        'jenis_pembayaran' => 'penuh',
        'status' => 'menunggu',
    ]);

    $lunas = buatCalon();
    $wajib = BiayaPendaftaran::where('tahun_ajaran_id', $lunas->tahun_ajaran_id)->where('wajib_bayar', true)->get();
    foreach ($wajib as $i => $biaya) {
        Pembayaran::create([
            'calon_siswa_id' => $lunas->id,
            'biaya_pendaftaran_id' => $biaya->id,
            'kode_pembayaran' => sprintf('PAY-2026-02%02d', $i),
            'jumlah' => $biaya->jumlah,
            'metode_pembayaran' => 'tunai',
            'jenis_pembayaran' => 'penuh',
            'status' => 'berhasil',
        ]);
    }

    $response = $this->actingAs(superAdmin())->get(route('admin.pembayarans.create'));

    $response->assertOk();
    $response->assertSee($belum->no_pendaftaran, false);
    $response->assertDontSee($lunas->no_pendaftaran, false);
});

test('store satu langkah angsuran membuat DP + rencana + cicilan', function () {
    $calon = buatCalon();
    $biaya = BiayaPendaftaran::where('jenis_biaya', 'Uang Pangkal')->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.pembayarans.store'), [
        'calon_siswa_id' => $calon->id,
        'biaya_pendaftaran_id' => $biaya->id,
        'jumlah' => $biaya->jumlah,
        'metode_pembayaran' => 'transfer',
        'bukti_pembayaran_path' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        'buat_angsuran' => 1,
        'dp_dibayar' => 500000,
        'jumlah_cicilan' => 2,
        'tanggal_mulai' => now()->toDateString(),
    ]);

    $response->assertRedirect(route('admin.pembayarans.index'));

    $rencana = RencanaAngsuran::where('calon_siswa_id', $calon->id)
        ->where('biaya_pendaftaran_id', $biaya->id)
        ->where('status', 'aktif')
        ->first();

    expect($rencana)->not->toBeNull()
        ->and($rencana->jumlah_cicilan)->toBe(2)
        ->and($rencana->detailAngsuran()->count())->toBe(2);

    $dp = Pembayaran::where('calon_siswa_id', $calon->id)
        ->where('jenis_pembayaran', 'dp_angsuran')
        ->first();

    expect($dp)->not->toBeNull()->and($dp->status)->toBe('berhasil');
});

test('store via modal show kembali ke show calon', function () {
    $calon = buatCalon();

    $response = $this->actingAs(superAdmin())->post(route('admin.pembayarans.store'), [
        'calon_siswa_id' => $calon->id,
        'jumlah' => 100000,
        'metode_pembayaran' => 'tunai',
        'redirect_to' => route('admin.calon-siswas.show', $calon),
    ]);

    $response->assertRedirect(route('admin.calon-siswas.show', $calon));
});

test('tamu tidak dapat export pembayaran', function () {
    $this->get(route('admin.pembayarans.export.excel'))->assertRedirect(route('login'));
    $this->get(route('admin.pembayarans.export.pdf'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak export pembayaran', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.pembayarans.export.excel'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.pembayarans.export.pdf'))->assertForbidden();
});

test('export excel mengikuti filter dan mengunduh xlsx', function () {
    $calon = buatCalon();
    Pembayaran::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'PAY-2026-0201',
        'jumlah' => 100000,
        'metode_pembayaran' => 'tunai',
        'jenis_pembayaran' => 'penuh',
        'status' => 'berhasil',
        'tanggal_pembayaran' => '2026-06-01',
    ]);

    $response = $this->actingAs(superAdmin())->get(route('admin.pembayarans.export.excel', ['status' => 'berhasil']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheetml');
    expect($response->headers->get('content-disposition'))->toContain('.xlsx');
});

test('export pdf mengunduh dokumen PDF dengan total', function () {
    $calon = buatCalon();
    Pembayaran::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'PAY-2026-0202',
        'jumlah' => 250000,
        'metode_pembayaran' => 'transfer',
        'jenis_pembayaran' => 'penuh',
        'status' => 'berhasil',
        'tanggal_pembayaran' => '2026-06-02',
    ]);

    $response = $this->actingAs(superAdmin())->get(route('admin.pembayarans.export.pdf'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('filter tanggal mulai sampai hanya kembalikan baris dalam rentang', function () {
    $calon = buatCalon();
    Pembayaran::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'PAY-2026-0301',
        'jumlah' => 100000,
        'metode_pembayaran' => 'tunai',
        'jenis_pembayaran' => 'penuh',
        'status' => 'berhasil',
        'tanggal_pembayaran' => '2026-05-10',
    ]);
    Pembayaran::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'PAY-2026-0302',
        'jumlah' => 100000,
        'metode_pembayaran' => 'tunai',
        'jenis_pembayaran' => 'penuh',
        'status' => 'berhasil',
        'tanggal_pembayaran' => '2026-07-20',
    ]);

    $response = $this->actingAs(superAdmin())->get(route('admin.pembayarans.index', [
        'tanggal_mulai' => '2026-07-01',
        'tanggal_sampai' => '2026-07-31',
    ]));

    $response->assertOk();
    $response->assertSee('PAY-2026-0302', false);
    $response->assertDontSee('PAY-2026-0301', false);
});

test('store angsuran ditolak bila biaya tidak dapat diangsur', function () {
    $calon = buatCalon();
    $biaya = BiayaPendaftaran::where('jenis_biaya', 'Seragam')->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.pembayarans.store'), [
        'calon_siswa_id' => $calon->id,
        'biaya_pendaftaran_id' => $biaya->id,
        'jumlah' => $biaya->jumlah,
        'metode_pembayaran' => 'transfer',
        'bukti_pembayaran_path' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        'buat_angsuran' => 1,
        'dp_dibayar' => 100000,
        'jumlah_cicilan' => 2,
        'tanggal_mulai' => now()->toDateString(),
    ]);

    $response->assertSessionHas('error');
    expect(RencanaAngsuran::where('calon_siswa_id', $calon->id)->exists())->toBeFalse();
});
