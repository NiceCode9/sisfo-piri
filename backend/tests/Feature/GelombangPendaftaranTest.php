<?php

use App\Models\BiayaPendaftaran;
use App\Models\CalonSiswa;
use App\Models\Gelombang;
use App\Models\JalurPendaftaran;
use App\Models\Pembayaran;
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

/**
 * Buat gelombang dengan jendela waktu yang sepenuhnya terkendali.
 *
 * Seeder membuat tanggal relatif terhadap hari ini, jadi test butuh cara
 * eksplisit untuk menguji gate tanggal. Tanggal diberikan sebagai baris tahap
 * `pendaftaran`, karena itulah yang jadi gate.
 *
 * @param  array<string, mixed>  $ubah
 */
function buatGelombang(array $ubah = []): Gelombang
{
    $tahun = TahunAjaran::aktif()->first()->id;
    $urut = (int) Gelombang::max('nomor_urut') + 1;

    $buka = $ubah['buka'] ?? now()->subWeek()->toDateString();
    $tutup = $ubah['tutup'] ?? now()->addWeek()->toDateString();

    unset($ubah['buka'], $ubah['tutup']);

    $payload = $ubah + [
        'tahun_ajaran_id' => $tahun,
        'nama_gelombang' => 'Gelombang Uji',
        'nomor_urut' => $urut,
        'kuota' => 30,
        'terisi' => 0,
        'is_aktif' => true,
    ];

    $gelombang = Gelombang::create($payload);

    $gelombang->tahapan()->create([
        'tipe' => 'pendaftaran',
        'nama_tahap' => 'Pendaftaran Online',
        'urutan' => 1,
        'tanggal_mulai' => $buka,
        'tanggal_selesai' => $tutup,
    ]);

    return $gelombang->load('tahapan');
}

/**
 * @param  array<string, mixed>  $ubah
 * @return array<string, mixed>
 */
function payloadWave(array $ubah = []): array
{
    static $urut = 0;
    $urut++;

    return array_merge([
        'jalur_pendaftaran_id' => JalurPendaftaran::first()->id,
        'nama_lengkap' => 'Siswa Gelombang',
        'jenis_kelamin' => 'L',
        'nik' => str_pad('77'.$urut, 16, '0', STR_PAD_LEFT),
        'nisn' => str_pad('88'.$urut, 10, '0', STR_PAD_LEFT),
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-05-10',
        'agama' => 'Islam',
        'asal_sekolah' => 'SD 1',
        'alamat' => 'Jl Gelombang',
        'no_hp' => '081234567890',
        'email' => "gelombang{$urut}@example.com",
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
    ], $ubah);
}

/**
 * Kandidat yang sudah punya akun, seperti hasil pendaftaran publik.
 *
 * `updateStatus()` hanya membuat tagihan bila kandidat punya `user_id`, jadi
 * test diskon harus menyiapkan kandidat seperti itu.
 *
 * @param  array<string, mixed>  $ubah
 */
function calonTerima(array $ubah = []): CalonSiswa
{
    $user = User::factory()->create();

    return CalonSiswa::create(array_merge([
        'jalur_pendaftaran_id' => JalurPendaftaran::first()->id,
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'user_id' => $user->id,
        'no_pendaftaran' => 'PPDB-2026-90'.random_int(10, 99),
        'nik' => (string) random_int(5550001112223330, 5550001112223399),
        'nama_lengkap' => 'Calon Diskon',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Diskon',
        'status_pendaftaran' => 'menunggu',
    ], $ubah));
}

test('gelombang terpilih tersimpan pada kandidat dan menambah counter', function () {
    Gelombang::query()->delete();
    $gelombang = buatGelombang();

    $this->post(route('spmb.store'), payloadWave(['gelombang_id' => $gelombang->id]))
        ->assertSessionHasNoErrors();

    $calon = CalonSiswa::where('email', 'gelombang1@example.com')->firstOrFail();

    expect($calon->gelombang_id)->toBe($gelombang->id)
        ->and($calon->gelombang->nama_gelombang)->toBe('Gelombang Uji')
        ->and($gelombang->fresh()->terisi)->toBe(1);
});

test('daftar pendaftaran menampilkan gelombang terbuka beserta sisa kursi', function () {
    Gelombang::query()->delete();
    $gelombang = buatGelombang(['kuota' => 30, 'terisi' => 12]);

    $html = $this->get(route('spmb.pendaftaran'))->assertOk()->getContent();

    expect($html)->toContain('Gelombang Uji')
        ->and($html)->toContain('sisa 18 kursi');
});

test('gelombang yang belum mulai tidak bisa dipilih maupun diterima', function () {
    Gelombang::query()->delete();
    $belum = buatGelombang([
        'nama_gelombang' => 'Belum Mulai',
        'buka' => now()->addWeek()->toDateString(),
        'tutup' => now()->addMonth()->toDateString(),
    ]);

    $html = $this->get(route('spmb.pendaftaran'))->assertOk()->getContent();

    // Gelombang yang belum dibuka TIDAK boleh jadi pilihan di dropdown.
    // Namanya sendiri boleh muncul di peringatan "belum dibuka" - itu
    // informasi yang justru dicari calon.
    preg_match('/<select id="gelombang_id".*?<\/select>/s', $html, $m);
    expect($m[0] ?? '')->not->toContain('Belum Mulai')
        ->and($html)->toContain('Pendaftaran Belum Dibuka');

    // POST langsung juga ditolak.
    $this->post(route('spmb.store'), payloadWave(['gelombang_id' => $belum->id]))
        ->assertSessionHasErrors('gelombang_id');

    expect(CalonSiswa::count())->toBe(0);
});

test('gelombang yang sudah lewat tanggal tutup tidak ditawarkan maupun diterima', function () {
    Gelombang::query()->delete();
    $lewat = buatGelombang([
        'nama_gelombang' => 'Sudah Lewat',
        'buka' => now()->subMonth()->toDateString(),
        'tutup' => now()->subDay()->toDateString(),
    ]);

    $html = $this->get(route('spmb.pendaftaran'))->assertOk()->getContent();

    // Bukan ditawarkan: tidak muncul sebagai <option> di dropdown gelombang.
    expect(opsiGelombang($html))->not->toContain('value="'.$lewat->id.'"');

    $this->post(route('spmb.store'), payloadWave(['gelombang_id' => $lewat->id]))
        ->assertSessionHasErrors('gelombang_id');

    expect(CalonSiswa::count())->toBe(0);
});

test('gelombang non-aktif tidak ditawarkan maupun diterima', function () {
    Gelombang::query()->delete();
    $nonAktif = buatGelombang(['nama_gelombang' => 'Non Aktif', 'is_aktif' => false]);

    $html = $this->get(route('spmb.pendaftaran'))->assertOk()->getContent();
    expect($html)->not->toContain('Non Aktif');

    $this->post(route('spmb.store'), payloadWave(['gelombang_id' => $nonAktif->id]))
        ->assertSessionHasErrors('gelombang_id');

    expect(CalonSiswa::count())->toBe(0);
});

test('kuota gelombang penuh menutup pendaftaran gelombang tersebut', function () {
    Gelombang::query()->delete();
    $penuh = buatGelombang(['nama_gelombang' => 'Penuh', 'kuota' => 2, 'terisi' => 2]);

    $html = $this->get(route('spmb.pendaftaran'))->assertOk()->getContent();

    // Penuh berarti tidak boleh dipilih, tapi boleh dilihat: calon perlu tahu
    // batch ini sudah penuh dan kapan batch berikutnya dibuka.
    expect(opsiGelombang($html))->not->toContain('value="'.$penuh->id.'"')
        ->and($html)->toContain('Kuota Penuh');

    $this->post(route('spmb.store'), payloadWave(['gelombang_id' => $penuh->id]))
        ->assertSessionHasErrors('gelombang_id');

    expect(CalonSiswa::count())->toBe(0);
    // Counter tidak boleh naik karena pendaftaran ditolak.
    expect($penuh->fresh()->terisi)->toBe(2);
});

test('wajib memilih gelombang selama masih ada gelombang terbuka', function () {
    Gelombang::query()->delete();
    buatGelombang();

    $this->post(route('spmb.store'), payloadWave(['gelombang_id' => null]))
        ->assertSessionHasErrors('gelombang_id');

    expect(CalonSiswa::count())->toBe(0);
});

test('pendaftaran tetap jalan bila sekolah belum mengatur gelombang', function () {
    Gelombang::query()->delete();

    $this->post(route('spmb.store'), payloadWave(['gelombang_id' => null]))
        ->assertSessionHasNoErrors();

    expect(CalonSiswa::count())->toBe(1)
        ->and(CalonSiswa::first()->gelombang_id)->toBeNull();
});

/**
 * Isi dropdown gelombang saja.
 *
 * Halaman pendaftaran punya beberapa `<select>` (jalur, gelombang, kota, ...),
 * jadi assertion harus seizing blok yang tepat. Kalau tidak, `value="1"` milik
 * select lain membuat test lulus atau gagal tanpa alasan.
 */
function opsiGelombang(string $html): string
{
    preg_match('/<select id="gelombang_id".*?<\/select>/s', $html, $cocok);

    return $cocok[0] ?? '';
}

test('landing page menampilkan gelombang yang belum ditutup dan menyembunyikan yang lewat', function () {
    Gelombang::query()->delete();
    buatGelombang(['nama_gelombang' => 'Yang Dibuka']);
    buatGelombang(['nama_gelombang' => 'Sudah Penuh', 'kuota' => 5, 'terisi' => 5]);
    buatGelombang([
        'nama_gelombang' => 'Sudah Lewat',
        'buka' => now()->subMonth()->toDateString(),
        'tutup' => now()->subDay()->toDateString(),
    ]);

    $html = $this->get(route('spmb.home'))->assertOk()->getContent();

    // Gelombang yang masih akan datang dan yang sedang dibuka sama-sama
    // tampil - "batch berikutnya kapan dibuka" itu informasi yang dicari calon.
    // Yang sudah lewat disembunyikan supaya halaman publik tidak menampilkan
    // tanggal basi.
    expect($html)->toContain('Yang Dibuka')
        ->and($html)->toContain('Sudah Penuh')
        ->and($html)->not->toContain('Sudah Lewat');
});

test('timeline pendaftaran dibangun dari tahap tiap gelombang', function () {
    Gelombang::query()->delete();

    $gelombang = buatGelombang(['nama_gelombang' => 'Gelombang Unmarshal']);
    $gelombang->tahapan()->create([
        'tipe' => 'tes',
        'nama_tahap' => 'Tes Seleksi Gelombang Unmarshal',
        'urutan' => 2,
        'tanggal_mulai' => now()->addWeek()->toDateString(),
    ]);

    $html = $this->get(route('spmb.pendaftaran'))->assertOk()->getContent();

    // Setiap tahap jadi baris timeline sendiri, dan nama gelombang muncul
    // sebagai label supaya tahap tidak terlihat melayang tanpa konteks.
    expect($html)->toContain('Pendaftaran Online')
        ->and($html)->toContain('Tes Seleksi Gelombang Unmarshal')
        ->and($html)->toContain('Gelombang Unmarshal')
        ->and($html)->toContain('Dibuka');
});

test('sisa kursi dan persentase memakai data nyata', function () {
    Gelombang::query()->delete();
    $gelombang = buatGelombang(['kuota' => 40, 'terisi' => 10]);

    expect($gelombang->sisa_kursi)->toBe(30)
        ->and($gelombang->persentase)->toBe(25.0)
        ->and($gelombang->kuotaPenuh())->toBeFalse();

    $gelombang->update(['terisi' => 40]);
    expect($gelombang->fresh()->kuotaPenuh())->toBeTrue()
        ->and($gelombang->fresh()->sisa_kursi)->toBe(0);
});

test('kuota nol berarti gelombang tidak dibatasi', function () {
    Gelombang::query()->delete();
    $tanpaBatas = buatGelombang(['kuota' => 0, 'terisi' => 999]);

    expect($tanpaBatas->sisa_kursi)->toBeNull()
        ->and($tanpaBatas->kuotaPenuh())->toBeFalse()
        ->and($tanpaBatas->persentase)->toBe(0.0)
        ->and($tanpaBatas->bisaMasuk())->toBeTrue();
});

test('diskon gelombang dijepit di rentang 0 sampai 100', function () {
    expect(buatGelombang(['nama_gelombang' => 'D1', 'diskon_persen' => 150])->diskon_efektif)->toBe(100)
        ->and(buatGelombang(['nama_gelombang' => 'D2', 'diskon_persen' => -20])->diskon_efektif)->toBe(0)
        ->and(buatGelombang(['nama_gelombang' => 'D3', 'diskon_persen' => 25])->diskon_efektif)->toBe(25);
});

test('counter gelombang turun ketika kandidat dihapus', function () {
    Gelombang::query()->delete();
    $gelombang = buatGelombang();

    $this->post(route('spmb.store'), payloadWave(['gelombang_id' => $gelombang->id]))
        ->assertSessionHasNoErrors();

    $calon = CalonSiswa::firstOrFail();
    expect($gelombang->fresh()->terisi)->toBe(1);

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin)->delete(route('admin.calon-siswas.destroy', $calon))->assertRedirect();

    expect($gelombang->fresh()->terisi)->toBe(0);
});

test('diskon gelombang memotong biaya pendaftaran saat kandidat diterima', function () {
    Gelombang::query()->delete();
    $gelombang = buatGelombang(['diskon_persen' => 50]);

    $biayaDaftar = BiayaPendaftaran::where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
        ->where('jenis_biaya', 'Biaya Pendaftaran')
        ->firstOrFail();

    $calon = calonTerima([
        'gelombang_id' => $gelombang->id,
        'no_pendaftaran' => 'PPDB-2026-9001',
        'nik' => '5550001112223334',
        'nama_lengkap' => 'Diskon Test',
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin)
        ->patch(route('admin.calon-siswas.status', $calon), ['status' => 'diterima'])
        ->assertRedirect();

    $tagihan = Pembayaran::where('calon_siswa_id', $calon->id)
        ->where('biaya_pendaftaran_id', $biayaDaftar->id)
        ->firstOrFail();

    // Diskon 50% dari 100.000 = 50.000.
    expect((float) $tagihan->jumlah)->toBe((float) ($biayaDaftar->jumlah - 50000));
});

test('diskon gelombang tidak memotong biaya wajib selain pendaftaran', function () {
    Gelombang::query()->delete();
    $gelombang = buatGelombang(['diskon_persen' => 50]);

    $tahun = TahunAjaran::aktif()->first()->id;

    $calon = calonTerima([
        'gelombang_id' => $gelombang->id,
        'no_pendaftaran' => 'PPDB-2026-9002',
        'nik' => '5550001112223335',
        'nama_lengkap' => 'Tanpa Diskon Lain',
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin)
        ->patch(route('admin.calon-siswas.status', $calon), ['status' => 'diterima'])
        ->assertRedirect();

    // Setiap biaya wajib selain pendaftaran tetap memakai harga normal.
    $lainnya = BiayaPendaftaran::where('tahun_ajaran_id', $tahun)
        ->where('wajib_bayar', true)
        ->where('jenis_biaya', '!=', 'Biaya Pendaftaran')
        ->get();

    foreach ($lainnya as $biaya) {
        $tagihan = Pembayaran::where('calon_siswa_id', $calon->id)
            ->where('biaya_pendaftaran_id', $biaya->id)
            ->firstOrFail();

        expect((float) $tagihan->jumlah)->toBe((float) $biaya->jumlah);
    }
});

test('kandidat tanpa gelombang tidak dikenai diskon', function () {
    Gelombang::query()->delete();

    $tahun = TahunAjaran::aktif()->first()->id;
    $biayaDaftar = BiayaPendaftaran::where('tahun_ajaran_id', $tahun)
        ->where('jenis_biaya', 'Biaya Pendaftaran')
        ->firstOrFail();

    $calon = calonTerima([
        'no_pendaftaran' => 'PPDB-2026-9003',
        'nik' => '5550001112223336',
        'nama_lengkap' => 'Tanpa Gelombang',
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin)
        ->patch(route('admin.calon-siswas.status', $calon), ['status' => 'diterima'])
        ->assertRedirect();

    $tagihan = Pembayaran::where('calon_siswa_id', $calon->id)
        ->where('biaya_pendaftaran_id', $biayaDaftar->id)
        ->firstOrFail();

    expect((float) $tagihan->jumlah)->toBe((float) $biayaDaftar->jumlah);
});
