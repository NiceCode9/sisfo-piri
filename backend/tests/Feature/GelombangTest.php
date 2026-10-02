<?php

use App\Models\Gelombang;
use App\Models\GelombangTahap;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
});

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

test('tamu tidak dapat membuka daftar gelombang', function () {
    $this->get(route('admin.gelombangs.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka daftar gelombang', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.gelombangs.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar gelombang', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.gelombangs.index'));
    $response->assertOk()->assertSee('Daftar Gelombang');
});

test('super-admin dapat menambah gelombang', function () {
    $tahun = TahunAjaran::aktif()->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.gelombangs.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'nama_gelombang' => 'Gelombang Test',
        'nomor_urut' => 99,
        'badge' => 'TEST',
        'tahapan' => [
            ['tipe' => 'pendaftaran', 'nama_tahap' => 'Pendaftaran Online', 'tanggal_mulai' => '2026-09-01', 'tanggal_selesai' => '2026-09-30'],
            ['tipe' => 'verifikasi', 'nama_tahap' => 'Verifikasi Berkas', 'tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2026-10-08'],
            ['tipe' => 'tes', 'nama_tahap' => 'Tes Seleksi', 'tanggal_mulai' => '2026-10-05', 'tanggal_selesai' => null],
            ['tipe' => 'pengumuman', 'nama_tahap' => 'Pengumuman Hasil', 'tanggal_mulai' => '2026-10-10', 'tanggal_selesai' => null],
        ],
        'kuota' => 50,
        'terisi' => 0,
        'diskon_persen' => 10,
        'keuntungan' => ['Gratis tas', 'Prioritas'],
        'keterangan' => 'Test',
        'warna_border' => 'primary-600',
        'is_aktif' => 1,
    ]);

    $response->assertRedirect(route('admin.gelombangs.index'));
    $gel = Gelombang::where('nama_gelombang', 'Gelombang Test')->first();
    expect($gel)->not->toBeNull()
        ->and($gel->keuntungan)->toBe(['Gratis tas', 'Prioritas'])
        ->and($gel->is_aktif)->toBeTrue();

    // Tahapan disimpan sebagai baris, urut sesuai urutan form.
    expect($gel->tahapan->pluck('tipe')->all())->toBe([
        'pendaftaran', 'verifikasi', 'tes', 'pengumuman',
    ]);

    $pendaftaran = $gel->tahapan->firstWhere('tipe', 'pendaftaran');
    expect($pendaftaran->tanggal_mulai->toDateString())->toBe('2026-09-01')
        ->and($pendaftaran->tanggal_selesai->toDateString())->toBe('2026-09-30');

    // Tahap satu hari: tanggal_selesai NULL berarti sama dengan tanggal_mulai.
    $tes = $gel->tahapan->firstWhere('tipe', 'tes');
    expect($tes->tanggal_selesai)->toBeNull()
        ->and($tes->tanggalAkhir()->toDateString())->toBe('2026-10-05')
        ->and($tes->punyaJendela())->toBeFalse();
});

test('gelombang tanpa tahap pendaftaran ditolak', function () {
    $tahun = TahunAjaran::aktif()->first();

    // Tanpa tahap pendaftaran, gelombang tidak akan pernah bisa dipilih
    // pendaftar karena itulah tahap yang jadi gate.
    $response = $this->actingAs(superAdmin())->post(route('admin.gelombangs.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'nama_gelombang' => 'Tanpa Daftar',
        'nomor_urut' => 98,
        'tahapan' => [
            ['tipe' => 'tes', 'nama_tahap' => 'Tes Seleksi', 'tanggal_mulai' => '2026-10-05', 'tanggal_selesai' => null],
        ],
        'kuota' => 50,
        'is_aktif' => 1,
    ]);

    $response->assertSessionHasErrors('tahapan');
    expect(Gelombang::where('nama_gelombang', 'Tanpa Daftar')->exists())->toBeFalse();
});

test('tahapan wajib berurutan kronologis', function () {
    $tahun = TahunAjaran::aktif()->first();

    // Tes seleksi 2 minggu SEBELUM pendaftaran dibuka - persis kesalahan yang
    // dulu lolos karena tanggal dihitung dari tanggal buka.
    $response = $this->actingAs(superAdmin())->post(route('admin.gelombangs.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'nama_gelombang' => 'Urutan Salah',
        'nomor_urut' => 97,
        'tahapan' => [
            ['tipe' => 'pendaftaran', 'nama_tahap' => 'Pendaftaran Online', 'tanggal_mulai' => '2026-09-01', 'tanggal_selesai' => '2026-09-30'],
            ['tipe' => 'tes', 'nama_tahap' => 'Tes Seleksi', 'tanggal_mulai' => '2026-08-20', 'tanggal_selesai' => null],
        ],
        'kuota' => 50,
        'is_aktif' => 1,
    ]);

    $response->assertSessionHasErrors('tahapan.1.tanggal_mulai');
    expect(Gelombang::where('nama_gelombang', 'Urutan Salah')->exists())->toBeFalse();
});

test('gelombang wajib punya minimal satu tahap', function () {
    $tahun = TahunAjaran::aktif()->first();

    $this->actingAs(superAdmin())->post(route('admin.gelombangs.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'nama_gelombang' => 'Tanpa Tahap',
        'nomor_urut' => 96,
        'tahapan' => [],
        'kuota' => 50,
        'is_aktif' => 1,
    ])->assertSessionHasErrors('tahapan');

    expect(Gelombang::where('nama_gelombang', 'Tanpa Tahap')->exists())->toBeFalse();
});

test('validasi tahap menolak tanggal selesai sebelum tanggal mulai', function () {
    $tahun = TahunAjaran::aktif()->first();

    $this->actingAs(superAdmin())->post(route('admin.gelombangs.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'nama_gelombang' => 'Gagal',
        'nomor_urut' => 99,
        'tahapan' => [
            ['tipe' => 'pendaftaran', 'nama_tahap' => 'Pendaftaran Online', 'tanggal_mulai' => '2026-10-10', 'tanggal_selesai' => '2026-10-01'],
        ],
        'kuota' => 10,
        'is_aktif' => 1,
    ])->assertSessionHasErrors('tahapan.0.tanggal_selesai');
});

test('validasi diskon melebihi 100 ditolak', function () {
    $tahun = TahunAjaran::aktif()->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.gelombangs.store'), [
        'tahun_ajaran_id' => $tahun->id,
        'nama_gelombang' => 'Gagal Diskon',
        'nomor_urut' => 99,
        'tahapan' => [
            ['tipe' => 'pendaftaran', 'nama_tahap' => 'Pendaftaran Online', 'tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2026-10-31'],
        ],
        'kuota' => 10,
        'diskon_persen' => 150,
        'is_aktif' => 1,
    ]);

    $response->assertSessionHasErrors('diskon_persen');
});

test('super-admin dapat memperbarui gelombang beserta tahapan', function () {
    $gel = Gelombang::with('tahapan')->firstOrFail();

    $response = $this->actingAs(superAdmin())->put(route('admin.gelombangs.update', $gel), [
        'tahun_ajaran_id' => $gel->tahun_ajaran_id,
        'nama_gelombang' => $gel->nama_gelombang.' Updated',
        'nomor_urut' => $gel->nomor_urut,
        'tahapan' => [
            ['tipe' => 'pendaftaran', 'nama_tahap' => 'Pendaftaran Online', 'tanggal_mulai' => '2027-01-01', 'tanggal_selesai' => '2027-01-31'],
        ],
        'kuota' => $gel->kuota,
        'is_aktif' => 1,
    ]);

    $response->assertRedirect(route('admin.gelombangs.index'));
    $gel->refresh();
    expect($gel->nama_gelombang)->toContain('Updated');

    // Tahap yang tidak ada lagi di form ikut terhapus: daftar dikirim utuh
    // setiap kali disimpan, jadi form ini adalah sumber kebenarannya.
    expect($gel->tahapan)->toHaveCount(1)
        ->and($gel->tahapan->first()->tanggal_mulai->toDateString())->toBe('2027-01-01');
});

test('super-admin dapat menghapus gelombang', function () {
    $tahun = TahunAjaran::aktif()->first();
    $gel = Gelombang::create([
        'tahun_ajaran_id' => $tahun->id,
        'nama_gelombang' => 'Hapus Test',
        'nomor_urut' => 99,
        'kuota' => 10,
        'is_aktif' => true,
    ]);
    $gel->tahapan()->create([
        'tipe' => 'pendaftaran',
        'nama_tahap' => 'Pendaftaran Online',
        'urutan' => 1,
        'tanggal_mulai' => '2026-09-01',
        'tanggal_selesai' => '2026-09-30',
    ]);

    $response = $this->actingAs(superAdmin())->delete(route('admin.gelombangs.destroy', $gel));
    $response->assertRedirect(route('admin.gelombangs.index'));
    expect(Gelombang::where('nama_gelombang', 'Hapus Test')->exists())->toBeFalse();

    // Tahap ikut terhapus lewat cascade, tidak tertinggal yatim.
    expect(GelombangTahap::where('gelombang_id', $gel->id)->exists())->toBeFalse();
});

test('admin tanpa permission delete tidak dapat menghapus gelombang', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $gel = Gelombang::first();

    $this->actingAs($admin)->delete(route('admin.gelombangs.destroy', $gel))->assertForbidden();
    expect(Gelombang::find($gel->id))->not->toBeNull();
});
