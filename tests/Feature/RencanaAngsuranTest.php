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

if (! function_exists('buatTagihanAngsuran')) {
    function buatTagihanAngsuran(): Pembayaran
    {
        static $n = 0;
        $n++;

        $calon = CalonSiswa::create([
            'jalur_pendaftaran_id' => JalurPendaftaran::first()->id,
            'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
            'no_pendaftaran' => sprintf('PPDB-2026-%04d', 500 + $n),
            'nik' => sprintf('990000000100%04d', $n),
            'nisn' => sprintf('990100%04d', $n),
            'nama_lengkap' => 'Calon Angsuran '.$n,
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Sleman',
            'tanggal_lahir' => '2010-01-01',
            'agama' => 'Islam',
            'alamat' => 'Jl Angsuran '.$n,
            'status_pendaftaran' => 'menunggu',
        ]);

        $biaya = BiayaPendaftaran::where('jenis_biaya', 'Uang Pangkal')->first();
        $biaya->update(['min_dp' => 500000, 'max_cicilan' => 3]);

        return Pembayaran::create([
            'calon_siswa_id' => $calon->id,
            'biaya_pendaftaran_id' => $biaya->id,
            'kode_pembayaran' => sprintf('PAY-2026-%04d', 500 + $n),
            'jumlah' => $biaya->jumlah,
            'metode_pembayaran' => 'transfer',
            'jenis_pembayaran' => 'penuh',
            'status' => 'menunggu',
        ]);
    }
}

if (! function_exists('buatRencana')) {
    function buatRencana($testCase, Pembayaran $tagihan, int $cicilan = 3, int $dp = 500000): RencanaAngsuran
    {
        $testCase->actingAs(superAdmin())->post(route('admin.rencana.store', $tagihan), [
            'dp_dibayar' => $dp,
            'jumlah_cicilan' => $cicilan,
            'tanggal_mulai' => '2026-09-01',
        ])->assertRedirect(route('admin.pembayarans.show', $tagihan));

        return RencanaAngsuran::where('pembayaran_id', $tagihan->id)->firstOrFail();
    }
}

test('tamu tidak dapat membuat rencana angsuran', function () {
    $tagihan = buatTagihanAngsuran();

    $this->post(route('admin.rencana.store', $tagihan))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuat rencana', function () {
    $user = User::factory()->create();
    $tagihan = buatTagihanAngsuran();

    $this->actingAs($user)->post(route('admin.rencana.store', $tagihan), [
        'dp_dibayar' => 500000,
        'jumlah_cicilan' => 3,
        'tanggal_mulai' => '2026-09-01',
    ])->assertForbidden();
});

test('buat rencana hasilkan DP + detail cicilan benar', function () {
    $tagihan = buatTagihanAngsuran();
    $rencana = buatRencana($this, $tagihan);

    expect($rencana->kode_angsuran)->toStartWith('ANG-')
        ->and((float) $rencana->total_biaya)->toBe(2500000.0)
        ->and((float) $rencana->dp_dibayar)->toBe(500000.0)
        ->and((float) $rencana->sisa_hutang)->toBe(2000000.0)
        ->and($rencana->detailAngsuran()->where('cicilan_ke', '>', 0)->count())->toBe(3);

    // 2.000.000 / 3 = 666.666 ×2 + 666.668 terakhir
    $nominals = $rencana->detailAngsuran()->where('cicilan_ke', '>', 0)->orderBy('cicilan_ke')->pluck('nominal_cicilan')->map(fn ($v) => (float) $v)->all();
    expect($nominals)->toBe([666666.0, 666666.0, 666668.0])
        ->and((float) $rencana->detailAngsuran()->where('cicilan_ke', '>', 0)->sum('nominal_cicilan'))->toBe(2000000.0);

    $dp = Pembayaran::where('calon_siswa_id', $tagihan->calon_siswa_id)->where('jenis_pembayaran', 'dp_angsuran')->first();
    expect($dp)->not->toBeNull()
        ->and($dp->status)->toBe('berhasil')
        ->and((float) $dp->jumlah)->toBe(500000.0);
});

test('DP di bawah minimum ditolak', function () {
    $tagihan = buatTagihanAngsuran();

    $response = $this->actingAs(superAdmin())->post(route('admin.rencana.store', $tagihan), [
        'dp_dibayar' => 100000,
        'jumlah_cicilan' => 3,
        'tanggal_mulai' => '2026-09-01',
    ]);

    $response->assertSessionHasErrors('dp_dibayar');
    expect(RencanaAngsuran::count())->toBe(0);
});

test('cicilan melebihi maksimum ditolak', function () {
    $tagihan = buatTagihanAngsuran();

    $response = $this->actingAs(superAdmin())->post(route('admin.rencana.store', $tagihan), [
        'dp_dibayar' => 500000,
        'jumlah_cicilan' => 5,
        'tanggal_mulai' => '2026-09-01',
    ]);

    $response->assertSessionHasErrors('jumlah_cicilan');
    expect(RencanaAngsuran::count())->toBe(0);
});

test('biaya tak dapat diangsur ditolak', function () {
    $tagihan = buatTagihanAngsuran();
    $tagihan->biayaPendaftaran->update(['dapat_diangsur' => false]);

    $response = $this->actingAs(superAdmin())->post(route('admin.rencana.store', $tagihan), [
        'dp_dibayar' => 500000,
        'jumlah_cicilan' => 3,
        'tanggal_mulai' => '2026-09-01',
    ]);

    $response->assertSessionHas('error');
    expect(RencanaAngsuran::count())->toBe(0);
});

test('rencana ganda untuk tagihan sama diblokir', function () {
    $tagihan = buatTagihanAngsuran();
    buatRencana($this, $tagihan);

    $response = $this->actingAs(superAdmin())->post(route('admin.rencana.store', $tagihan), [
        'dp_dibayar' => 500000,
        'jumlah_cicilan' => 3,
        'tanggal_mulai' => '2026-09-01',
    ]);

    $response->assertSessionHas('error');
    expect(RencanaAngsuran::count())->toBe(1);
});

test('bayar cicilan terverifikasi menutup detail dan kurangi sisa', function () {
    $tagihan = buatTagihanAngsuran();
    $rencana = buatRencana($this, $tagihan);
    $detail = $rencana->detailAngsuran()->where('cicilan_ke', 1)->first();

    $this->actingAs(superAdmin())->post(route('admin.pembayarans.store'), [
        'calon_siswa_id' => $tagihan->calon_siswa_id,
        'biaya_pendaftaran_id' => $tagihan->biaya_pendaftaran_id,
        'detail_angsuran_id' => $detail->id,
        'jumlah' => 666666,
        'metode_pembayaran' => 'tunai',
        'bukti_pembayaran_path' => UploadedFile::fake()->create('c1.pdf', 100, 'application/pdf'),
    ])->assertRedirect(route('admin.pembayarans.index'));

    $bayar = Pembayaran::where('detail_angsuran_id', $detail->id)->first();
    expect($bayar->jenis_pembayaran)->toBe('cicilan_angsuran');

    $this->actingAs(superAdmin())->patch(route('admin.pembayarans.status', $bayar), [
        'status' => 'berhasil',
    ])->assertRedirect();

    expect($detail->fresh()->status)->toBe('dibayar')
        ->and((float) $detail->fresh()->total_bayar)->toBe(666666.0)
        ->and((float) $rencana->fresh()->sisa_hutang)->toBe(1333334.0)
        ->and($rencana->fresh()->status)->toBe('aktif')
        ->and($tagihan->fresh()->status)->toBe('menunggu');
});

test('semua cicilan lunas menutup rencana dan induk', function () {
    $tagihan = buatTagihanAngsuran();
    $rencana = buatRencana($this, $tagihan, 2);

    foreach ($rencana->detailAngsuran()->where('cicilan_ke', '>', 0)->orderBy('cicilan_ke')->get() as $detail) {
        $this->actingAs(superAdmin())->post(route('admin.pembayarans.store'), [
            'calon_siswa_id' => $tagihan->calon_siswa_id,
            'biaya_pendaftaran_id' => $tagihan->biaya_pendaftaran_id,
            'detail_angsuran_id' => $detail->id,
            'jumlah' => (float) $detail->nominal_cicilan,
            'metode_pembayaran' => 'tunai',
        ])->assertRedirect(route('admin.pembayarans.index'));

        $bayar = Pembayaran::where('detail_angsuran_id', $detail->id)->first();

        $this->actingAs(superAdmin())->patch(route('admin.pembayarans.status', $bayar), [
            'status' => 'berhasil',
        ])->assertRedirect();
    }

    expect($rencana->fresh()->status)->toBe('lunas')
        ->and((float) $rencana->fresh()->sisa_hutang)->toBe(0.0)
        ->and($tagihan->fresh()->status)->toBe('berhasil');
});

test('denda manual masuk saran bayar dan tercatat', function () {
    $tagihan = buatTagihanAngsuran();
    $rencana = buatRencana($this, $tagihan);
    $detail = $rencana->detailAngsuran()->where('cicilan_ke', 1)->first();

    $this->actingAs(superAdmin())->patch(route('admin.rencana.denda', $detail), [
        'denda' => 50000,
    ])->assertRedirect();

    expect((float) $detail->fresh()->denda)->toBe(50000.0);

    $this->actingAs(superAdmin())->post(route('admin.pembayarans.store'), [
        'calon_siswa_id' => $tagihan->calon_siswa_id,
        'biaya_pendaftaran_id' => $tagihan->biaya_pendaftaran_id,
        'detail_angsuran_id' => $detail->id,
        'jumlah' => 716666,
        'metode_pembayaran' => 'tunai',
    ])->assertRedirect(route('admin.pembayarans.index'));

    $bayar = Pembayaran::where('detail_angsuran_id', $detail->id)->first();

    $this->actingAs(superAdmin())->patch(route('admin.pembayarans.status', $bayar), [
        'status' => 'berhasil',
    ])->assertRedirect();

    expect((float) $detail->fresh()->total_bayar)->toBe(716666.0);
});

test('batal diblokir bila sudah ada cicilan dibayar', function () {
    $tagihan = buatTagihanAngsuran();
    $rencana = buatRencana($this, $tagihan, 2);
    $detail = $rencana->detailAngsuran()->where('cicilan_ke', 1)->first();

    $this->actingAs(superAdmin())->post(route('admin.pembayarans.store'), [
        'calon_siswa_id' => $tagihan->calon_siswa_id,
        'biaya_pendaftaran_id' => $tagihan->biaya_pendaftaran_id,
        'detail_angsuran_id' => $detail->id,
        'jumlah' => (float) $detail->nominal_cicilan,
        'metode_pembayaran' => 'tunai',
    ]);

    $bayar = Pembayaran::where('detail_angsuran_id', $detail->id)->first();

    $this->actingAs(superAdmin())->patch(route('admin.pembayarans.status', $bayar), ['status' => 'berhasil']);

    $this->actingAs(superAdmin())->patch(route('admin.rencana.batal', $rencana))
        ->assertSessionHas('error');

    expect($rencana->fresh()->status)->toBe('aktif');
});

test('hapus pembayaran cicilan terverifikasi diblokir', function () {
    $tagihan = buatTagihanAngsuran();
    $rencana = buatRencana($this, $tagihan, 2);
    $detail = $rencana->detailAngsuran()->where('cicilan_ke', 1)->first();

    $this->actingAs(superAdmin())->post(route('admin.pembayarans.store'), [
        'calon_siswa_id' => $tagihan->calon_siswa_id,
        'biaya_pendaftaran_id' => $tagihan->biaya_pendaftaran_id,
        'detail_angsuran_id' => $detail->id,
        'jumlah' => (float) $detail->nominal_cicilan,
        'metode_pembayaran' => 'tunai',
    ]);

    $bayar = Pembayaran::where('detail_angsuran_id', $detail->id)->first();

    $this->actingAs(superAdmin())->patch(route('admin.pembayarans.status', $bayar), ['status' => 'berhasil']);

    $this->actingAs(superAdmin())->delete(route('admin.pembayarans.destroy', $bayar))
        ->assertSessionHas('error');

    expect(Pembayaran::find($bayar->id))->not->toBeNull();
});

test('DP tercatat sebagai baris cicilan ke-0 dan terhubung', function () {
    $tagihan = buatTagihanAngsuran();
    $rencana = buatRencana($this, $tagihan, 3, 500000);

    $dpDetail = $rencana->detailAngsuran()->where('cicilan_ke', 0)->first();

    expect($dpDetail)->not->toBeNull()
        ->and($dpDetail->status)->toBe('dibayar')
        ->and((float) $dpDetail->total_bayar)->toBe(500000.0);

    $dpBayar = Pembayaran::where('jenis_pembayaran', 'dp_angsuran')
        ->where('calon_siswa_id', $tagihan->calon_siswa_id)
        ->first();

    expect($dpBayar)->not->toBeNull()
        ->and($dpBayar->detail_angsuran_id)->toBe($dpDetail->id);
});

test('bayar 1 dari 3 via store langsung menutup tanpa patch', function () {
    $tagihan = buatTagihanAngsuran();
    $rencana = buatRencana($this, $tagihan, 3, 500000);
    $detail = $rencana->detailAngsuran()->where('cicilan_ke', 1)->first();

    $this->actingAs(superAdmin())->post(route('admin.pembayarans.store'), [
        'calon_siswa_id' => $tagihan->calon_siswa_id,
        'biaya_pendaftaran_id' => $tagihan->biaya_pendaftaran_id,
        'detail_angsuran_id' => $detail->id,
        'jumlah' => (float) $detail->nominal_cicilan,
        'metode_pembayaran' => 'tunai',
        'redirect_to' => route('admin.calon-siswas.show', $tagihan->calon_siswa_id),
    ])->assertRedirect(route('admin.calon-siswas.show', $tagihan->calon_siswa_id));

    expect($detail->fresh()->status)->toBe('dibayar')
        ->and($rencana->detailAngsuran()->where('cicilan_ke', '>', 0)->where('status', '!=', 'dibayar')->count())->toBe(2)
        ->and($rencana->fresh()->status)->toBe('aktif')
        ->and($tagihan->fresh()->status)->toBe('menunggu');
});

test('bayar semua via store menutup rencana dan induk otomatis', function () {
    $tagihan = buatTagihanAngsuran();
    $rencana = buatRencana($this, $tagihan, 2, 500000);

    foreach ($rencana->detailAngsuran()->where('cicilan_ke', '>', 0)->orderBy('cicilan_ke')->get() as $detail) {
        $this->actingAs(superAdmin())->post(route('admin.pembayarans.store'), [
            'calon_siswa_id' => $tagihan->calon_siswa_id,
            'biaya_pendaftaran_id' => $tagihan->biaya_pendaftaran_id,
            'detail_angsuran_id' => $detail->id,
            'jumlah' => (float) $detail->nominal_cicilan,
            'metode_pembayaran' => 'tunai',
        ])->assertRedirect(route('admin.pembayarans.index'));
    }

    expect($rencana->fresh()->status)->toBe('lunas')
        ->and((float) $rencana->fresh()->sisa_hutang)->toBe(0.0)
        ->and($tagihan->fresh()->status)->toBe('berhasil');
});

test('show calon sembunyikan bayar bila rencana aktif', function () {
    $tagihan = buatTagihanAngsuran();
    buatRencana($this, $tagihan, 3, 500000);

    $response = $this->actingAs(superAdmin())->get(route('admin.calon-siswas.show', $tagihan->calon_siswa_id));

    $response->assertOk();
    $response->assertSee('Dibayar via cicilan di bawah', false);
    $response->assertSee('modalBayarCicilan', false);
});
