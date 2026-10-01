<?php

use App\Models\BiayaPendaftaran;
use App\Models\CalonSiswa;
use App\Models\JalurPendaftaran;
use App\Models\Kelas;
use App\Models\KuotaPendaftaran;
use App\Models\LogStatusPendaftaran;
use App\Models\Pembayaran;
use App\Models\PembayaranLainnya;
use App\Models\RencanaAngsuran;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\WaliMurid;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
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

test('modal pembayaran show memuat dropdown seluruh biaya termasuk non-wajib', function () {
    $calon = CalonSiswa::create([
        'jalur_pendaftaran_id' => JalurPendaftaran::first()->id,
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'no_pendaftaran' => 'PPDB-2026-0002',
        'nik' => '1234567890123457',
        'nama_lengkap' => 'Show Biaya Test',
        'jenis_kelamin' => 'P',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-02-02',
        'agama' => 'Islam',
        'alamat' => 'Jl Test',
        'status_pendaftaran' => 'menunggu',
    ]);

    $response = $this->actingAs(superAdmin())->get(route('admin.calon-siswas.show', $calon));

    $response->assertOk();
    $response->assertSee('name="biaya_pendaftaran_id"', false);
    // non-wajib (Ekstrakurikuler) ikut tampil dengan label Opsional
    $response->assertSee('Opsional', false);
    foreach (['Uang Pangkal', 'Seragam', 'Ekstrakurikuler'] as $jenis) {
        $response->assertSee($jenis, false);
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
    Storage::disk('berkas')->assertExists($calon->berkasCalonSiswa->ijazah_path);
    expect($calon->logStatusPendaftaran()->count())->toBe(1);
});

test('baris sertifikat kosong dari form admin tidak menggagalkan jalur non-wajib', function () {
    $jalur = JalurPendaftaran::where('nama_jalur', 'Jalur Reguler')->first() ?? JalurPendaftaran::first();

    $response = $this->actingAs(superAdmin())->post(route('admin.calon-siswas.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'nik' => '1234567890123999',
        'nisn' => '1234567899',
        'nama_lengkap' => 'Sari Kosong',
        'jenis_kelamin' => 'P',
        'tempat_lahir' => 'Ngaglik',
        'tanggal_lahir' => '2010-02-02',
        'agama' => 'Islam',
        'alamat' => 'Jl Kosong 1',
        'no_hp' => '081234567899',
        'email' => 'sari@example.com',
        'nama_ayah' => 'Ayah Sari',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu Sari',
        'pekerjaan_ibu' => 'IRT',
        'no_hp_orang_tua' => '081234567898',
        // Simulasi browser: baris kosong tetap terkirim walau seksi disembunyikan.
        'sertifikat' => [['nama' => '']],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('admin.calon-siswas.index'));
    expect(CalonSiswa::where('nik', '1234567890123999')->exists())->toBeTrue();
});

test('edit tidak dapat memaksa status menjadi diterima', function () {
    $jalur = JalurPendaftaran::first();
    $calon = CalonSiswa::create([
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'no_pendaftaran' => 'PPDB-2026-0900',
        'nik' => '1234567890900111',
        'nama_lengkap' => 'Bypass Status',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Ngaglik',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Bypass',
        'status_pendaftaran' => 'menunggu',
    ]);
    $kuota = KuotaPendaftaran::where('tahun_ajaran_id', $calon->tahun_ajaran_id)
        ->where('jalur_pendaftaran_id', $jalur->id)->first();
    $terisiAwal = $kuota?->terisi ?? 0;

    // Coba bypass: kirim status_pendaftaran langsung lewat form edit.
    $this->actingAs(superAdmin())->put(route('admin.calon-siswas.update', $calon), [
        'jalur_pendaftaran_id' => $jalur->id,
        'nik' => '1234567890900111',
        'nama_lengkap' => 'Bypass Status',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Ngaglik',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Bypass',
        'status_pendaftaran' => 'diterima',
    ])->assertRedirect(route('admin.calon-siswas.show', $calon));

    // Status, kuota, log status, dan tabel siswa harus tidak tersentuh.
    expect($calon->fresh()->status_pendaftaran)->toBe('menunggu')
        ->and($kuota?->fresh()->terisi)->toBe($terisiAwal)
        ->and(LogStatusPendaftaran::where('calon_siswa_id', $calon->id)->count())->toBe(0)
        ->and(Siswa::where('calon_siswa_id', $calon->id)->exists())->toBeFalse();
});

test('tambah calon selalu berstatus menunggu meski admin kirim accepted', function () {
    $jalur = JalurPendaftaran::first();

    $this->actingAs(superAdmin())->post(route('admin.calon-siswas.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'nik' => '1234567890900222',
        'nama_lengkap' => 'Langsung Diterima',
        'jenis_kelamin' => 'P',
        'tempat_lahir' => 'Ngaglik',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Langsung',
        'status_pendaftaran' => 'diterima',
    ])->assertRedirect(route('admin.calon-siswas.index'));

    $calon = CalonSiswa::where('nik', '1234567890900222')->first();

    expect($calon)->not->toBeNull()
        ->and($calon->status_pendaftaran)->toBe('menunggu')
        ->and(Siswa::where('calon_siswa_id', $calon->id)->exists())->toBeFalse();
});

test('penolakan membatalkan tagihan menunggu, rencana aktif, dan akses wali', function () {
    $jalur = JalurPendaftaran::first();
    $calon = CalonSiswa::create([
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'user_id' => User::factory()->create()->id,
        'no_pendaftaran' => 'PPDB-2026-1100',
        'nik' => '1234567801100',
        'nama_lengkap' => 'Ditolak',
        'jenis_kelamin' => 'P',
        'tempat_lahir' => 'Ngaglik',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Ditolak',
        'status_pendaftaran' => 'menunggu',
    ]);

    $this->actingAs(superAdmin())
        ->patch(route('admin.calon-siswas.status', $calon), ['status' => 'diterima'])
        ->assertSessionHasNoErrors();

    $biaya = BiayaPendaftaran::where('tahun_ajaran_id', $calon->tahun_ajaran_id)->firstOrFail();
    $menunggu = Pembayaran::create([
        'calon_siswa_id' => $calon->id,
        'biaya_pendaftaran_id' => $biaya->id,
        'kode_pembayaran' => 'PAY-2026-1100',
        'jumlah' => 100000,
        'metode_pembayaran' => 'transfer',
        'jenis_pembayaran' => 'penuh',
        'status' => 'menunggu',
    ]);
    $sudahBayar = Pembayaran::create([
        'calon_siswa_id' => $calon->id,
        'biaya_pendaftaran_id' => $biaya->id,
        'kode_pembayaran' => 'PAY-2026-1101',
        'jumlah' => 50000,
        'metode_pembayaran' => 'transfer',
        'jenis_pembayaran' => 'penuh',
        'status' => 'berhasil',
    ]);
    $rencana = RencanaAngsuran::create([
        'calon_siswa_id' => $calon->id,
        'biaya_pendaftaran_id' => $biaya->id,
        'kode_angsuran' => 'ANG-2026-1100',
        'total_biaya' => 300000,
        'sisa_hutang' => 300000,
        'nominal_per_cicilan' => 100000,
        'jumlah_cicilan' => 3,
        'tanggal_mulai' => now()->toDateString(),
        'tanggal_selesai' => now()->addMonths(2)->toDateString(),
        'status' => 'aktif',
    ]);
    $lain = PembayaranLainnya::create([
        'calon_siswa_id' => $calon->id,
        'kode_pembayaran' => 'LNN-2026-1100',
        'nama_biaya' => 'Seragam',
        'jumlah' => 150000,
        'metode_pembayaran' => 'tunai',
        'status' => 'menunggu',
    ]);

    $siswa = Siswa::where('calon_siswa_id', $calon->id)->firstOrFail();
    $wali = WaliMurid::create([
        'user_id' => User::factory()->create()->id,
        'siswa_id' => $siswa->id,
        'hubungan' => 'Ibu',
    ]);

    $this->actingAs(superAdmin())
        ->patch(route('admin.calon-siswas.status', $calon), ['status' => 'ditolak'])
        ->assertSessionHasNoErrors();

    // Invoice yang masih menunggu dan rencana aktif dibatalkan.
    expect($menunggu->fresh()->status)->toBe('batal')
        ->and($lain->fresh()->status)->toBe('batal')
        ->and($rencana->fresh()->status)->toBe('batal')
        // Uang yang sudah masuk tidak dihapus/diubah.
        ->and($sudahBayar->fresh()->status)->toBe('berhasil')
        // Siswa dinonaktifkan dan wali loses access.
        ->and($siswa->fresh()->is_aktif)->toBeFalse()
        ->and(WaliMurid::where('id', $wali->id)->exists())->toBeFalse();
});

test('calon ditolak tanpa tagihan tidak error', function () {
    $jalur = JalurPendaftaran::first();
    $calon = CalonSiswa::create([
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'no_pendaftaran' => 'PPDB-2026-1200',
        'nik' => '1234567801200',
        'nama_lengkap' => 'Ditolak Kosong',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Ngaglik',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Kosong',
        'status_pendaftaran' => 'menunggu',
    ]);

    $this->actingAs(superAdmin())
        ->patch(route('admin.calon-siswas.status', $calon), ['status' => 'ditolak'])
        ->assertSessionHasNoErrors();

    expect($calon->fresh()->status_pendaftaran)->toBe('ditolak');
});

test('calon yang sudah jadi siswa tidak dapat dihapus dari halaman PPDB', function () {
    $jalur = JalurPendaftaran::first();
    $user = User::factory()->create();
    $calon = CalonSiswa::create([
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'user_id' => $user->id,
        'no_pendaftaran' => 'PPDB-2026-0700',
        'nik' => '1234567800700',
        'nama_lengkap' => 'Sudah Jadi Siswa',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Ngaglik',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Terlindungi',
        'status_pendaftaran' => 'menunggu',
    ]);

    $this->actingAs(superAdmin())
        ->patch(route('admin.calon-siswas.status', $calon), ['status' => 'diterima'])
        ->assertSessionHasNoErrors();

    $siswa = Siswa::where('calon_siswa_id', $calon->id)->first();
    expect($siswa)->not->toBeNull();

    // Beri riwayat agar cascade-delete benar-benar berbahaya bila tidak diguard.
    $tahunId = $calon->tahun_ajaran_id;
    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $siswa->kelas_id ?? Kelas::firstOrFail()->id,
        'tahun_ajaran_id' => $tahunId,
        'status' => 'aktif',
    ]);

    $this->actingAs(superAdmin())
        ->delete(route('admin.calon-siswas.destroy', $calon))
        ->assertSessionHas('error');

    // Semua data harus utuh.
    expect($calon->fresh())->not->toBeNull()
        ->and(Siswa::where('calon_siswa_id', $calon->id)->exists())->toBeTrue()
        ->and(RiwayatKelas::where('siswa_id', $siswa->id)->exists())->toBeTrue();
});

test('calon biasa (belum jadi siswa) tetap bisa dihapus', function () {
    $jalur = JalurPendaftaran::first();
    $calon = CalonSiswa::create([
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'no_pendaftaran' => 'PPDB-2026-0800',
        'nik' => '1234567800800',
        'nama_lengkap' => 'Belum Diterima',
        'jenis_kelamin' => 'P',
        'tempat_lahir' => 'Ngaglik',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Hapus',
        'status_pendaftaran' => 'menunggu',
    ]);

    $this->actingAs(superAdmin())
        ->delete(route('admin.calon-siswas.destroy', $calon))
        ->assertRedirect(route('admin.calon-siswas.index'));

    expect(CalonSiswa::where('id', $calon->id)->exists())->toBeFalse();
});

test('penomoran pendaftaran tetap unik meski ada nomor yang terhapus', function () {
    $jalur = JalurPendaftaran::first();
    $tahunId = TahunAjaran::aktif()->first()->id;

    $buat = function (string $nik, string $no) use ($jalur, $tahunId) {
        // NIK harus 16 digit (validasi admin).
        $nik = str_pad($nik, 16, '0', STR_PAD_LEFT);

        return CalonSiswa::create([
            'jalur_pendaftaran_id' => $jalur->id,
            'tahun_ajaran_id' => $tahunId,
            'no_pendaftaran' => $no,
            'nik' => $nik,
            'nama_lengkap' => 'Siswa '.$no,
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Ngaglik',
            'tanggal_lahir' => '2010-01-01',
            'agama' => 'Islam',
            'alamat' => 'Jl Nomor',
            'status_pendaftaran' => 'menunggu',
        ]);
    };

    // Tiga baris; nomor sengaja tidak berurutan agar hitungan "count" tidak cocok.
    $a = $buat('1234567800001', 'PPDB-2026-0001');
    $b = $buat('1234567800002', 'PPDB-2026-0002');
    $c = $buat('1234567800003', 'PPDB-2026-0003');

    // Hapus baris tengah — count() turun ke 2 (bug lama: nomor berikutnya jadi 0003 → bentrok).
    $b->delete();

    $response = $this->actingAs(superAdmin())->post(route('admin.calon-siswas.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'nik' => str_pad('1234567800004', 16, '0', STR_PAD_LEFT),
        'nama_lengkap' => 'Pendaftar Baru',
        'jenis_kelamin' => 'P',
        'tempat_lahir' => 'Ngaglik',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Baru',
    ]);

    $response->assertSessionHasNoErrors();
    $nikBaru = str_pad('1234567800004', 16, '0', STR_PAD_LEFT);
    $baru = CalonSiswa::where('nik', $nikBaru)->first();

    expect($baru)->not->toBeNull()
        ->and($baru->no_pendaftaran)->toStartWith('PPDB-'.date('Y').'-')
        // Berbeda dari nomor yang sudah dipakai.
        ->and($baru->no_pendaftaran)->not->toBe('PPDB-2026-0003')
        ->and($baru->no_pendaftaran)->not->toBe('PPDB-2026-0001')
        // Tidak ada duplikat di tabel.
        ->and(CalonSiswa::where('no_pendaftaran', $baru->no_pendaftaran)->count())->toBe(1);
});

test('penerimaan menghasilkan kode pembayaran berformat PAY dan unik', function () {
    $jalur = JalurPendaftaran::first();
    // Diterima hanya diproses bila calon punya akun (user_id) — meniru alur form publik.
    $user = User::factory()->create();
    $calon = CalonSiswa::create([
        'jalur_pendaftaran_id' => $jalur->id,
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'user_id' => $user->id,
        'no_pendaftaran' => 'PPDB-2026-0500',
        'nik' => '1234567800500',
        'nama_lengkap' => 'Bayar Uji',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Ngaglik',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Bayar',
        'status_pendaftaran' => 'menunggu',
    ]);

    $this->actingAs(superAdmin())
        ->patch(route('admin.calon-siswas.status', $calon), ['status' => 'diterima'])
        ->assertSessionHasNoErrors();

    $kodes = $calon->pembayaran()->pluck('kode_pembayaran');

    expect($kodes)->not->toBeEmpty()
        ->and($kodes->filter(fn ($k) => str_starts_with($k, 'PAY-'.date('Y')))->count())->toBe($kodes->count())
        ->and($kodes->unique()->count())->toBe($kodes->count());
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
