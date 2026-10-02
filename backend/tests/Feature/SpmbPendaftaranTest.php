<?php

use App\Models\CalonSiswa;
use App\Models\Ekstrakurikuler;
use App\Models\Gelombang;
use App\Models\Guru;
use App\Models\JalurPendaftaran;
use App\Models\KuotaPendaftaran;
use App\Models\ProfilSekolah;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\ProfilSekolahSeeder;
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
 * Gelombang yang jendelanya benar-benar terbuka, untuk disisipkan ke payload.
 *
 * Seeder membuat gelombang relatif terhadap hari ini, jadi test mengambil dari
 * database alih-alih menebak id.
 */
function gelombangTerbukaId(): ?int
{
    return Gelombang::terbuka(TahunAjaran::aktif()->first())->first()?->id;
}

/**
 * Payload pendaftaran publik yang valid; test cukup menimpa field yang relevan.
 *
 * @param  array<string, mixed>  $ubah
 * @return array<string, mixed>
 */
function payloadSpmb(array $ubah = []): array
{
    return array_merge([
        'jalur_pendaftaran_id' => JalurPendaftaran::first()->id,
        'gelombang_id' => gelombangTerbukaId(),
        'nama_lengkap' => 'Siswa Uji Gate',
        'jenis_kelamin' => 'L',
        'nik' => '1234567890123456',
        'nisn' => '1234567890',
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-05-10',
        'agama' => 'Islam',
        'asal_sekolah' => 'SMP 1',
        'alamat' => 'Jl Gate 1',
        'no_hp' => '081234567890',
        'email' => 'gate@example.com',
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

test('halaman pendaftaran dapat ditampilkan', function () {
    $response = $this->get(route('spmb.pendaftaran'));
    $response->assertOk()->assertSee('Pendaftaran');
});

test('pendaftaran publik sukses dengan 5 berkas', function () {
    $jalur = JalurPendaftaran::first();

    $response = $this->post(route('spmb.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'gelombang_id' => gelombangTerbukaId(),
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
    Storage::disk('berkas')->assertExists($calon->berkasCalonSiswa->ijazah_path);
});

test('halaman publik tidak punya tautan mati dan CTA mengarah ke tujuan yang benar', function () {
    foreach (['spmb.home', 'spmb.pendaftaran', 'spmb.about'] as $route) {
        $html = $this->get(route($route))->assertOk()->getContent();

        // Tidak boleh ada tautan yang tidak punya tujuan.
        expect($html)->not->toContain('href="#"');

        // Handler smooth-scroll harus mengubah preventDefault jadi kondisi terakhir,
        // bukan di awal — versi lama melempar DOMException untuk href="#".
        expect($html)->toContain("if (!href || href === '#' || href.length < 2)");
    }

    // Dua tombol konversi utama harus benar-benar menuju URL yang ada.
    $this->get(route('spmb.home'))
        ->assertSee('href="'.route('login').'"', escape: false)
        ->assertSee(route('spmb.pendaftaran'), escape: false);
});

test('pendaftaran publik menambah kuota pendaftaran, bukan kuota penerimaan', function () {
    $tahun = TahunAjaran::aktif()->first();
    $jalur = JalurPendaftaran::first();
    $kuota = KuotaPendaftaran::where('tahun_ajaran_id', $tahun->id)->where('jalur_pendaftaran_id', $jalur->id)->firstOrFail();
    $kuota->update(['kuota' => 10, 'terisi' => 0, 'kuota_pendaftaran' => 5, 'terisi_pendaftaran' => 0]);

    $this->post(route('spmb.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'gelombang_id' => gelombangTerbukaId(),
        'nama_lengkap' => 'Penghitung Kuota',
        'jenis_kelamin' => 'L',
        'nik' => '1234567890123500',
        'nisn' => '1234567350',
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-05-10',
        'agama' => 'Islam',
        'asal_sekolah' => 'SMP 1',
        'alamat' => 'Jl Kuota 1',
        'no_hp' => '081234567890',
        'email' => 'kuota@example.com',
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
    ])->assertSessionHasNoErrors();

    expect($kuota->fresh())
        ->terisi_pendaftaran->toBe(1)
        // Kuota penerimaan tidak boleh tersentuh oleh pendaftaran.
        ->terisi->toBe(0);
});

test('pendaftaran tetap boleh masuk saat kuota penerimaan penuh tapi kuota pendaftaran tersedia', function () {
    $tahun = TahunAjaran::aktif()->first();
    $jalur = JalurPendaftaran::first();
    $kuota = KuotaPendaftaran::where('tahun_ajaran_id', $tahun->id)->where('jalur_pendaftaran_id', $jalur->id)->firstOrFail();
    // Penerimaan sudah penuh, pendaftaran masih longgar.
    $kuota->update(['kuota' => 2, 'terisi' => 2, 'kuota_pendaftaran' => 100, 'terisi_pendaftaran' => 1]);

    $this->post(route('spmb.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'gelombang_id' => gelombangTerbukaId(),
        'nama_lengkap' => 'Penerimaan Penuh',
        'jenis_kelamin' => 'P',
        'nik' => '1234567890123600',
        'nisn' => '1234567360',
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-05-10',
        'agama' => 'Islam',
        'asal_sekolah' => 'SMP 1',
        'alamat' => 'Jl Kuota 2',
        'no_hp' => '081234567890',
        'email' => 'penuh-kuota@example.com',
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
    ])->assertSessionHasNoErrors();

    expect($kuota->fresh())->terisi_pendaftaran->toBe(2);
});

test('kuota pendaftaran kosong berarti pendaftaran tidak dibatasi', function () {
    $tahun = TahunAjaran::aktif()->first();
    $jalur = JalurPendaftaran::first();
    $kuota = KuotaPendaftaran::where('tahun_ajaran_id', $tahun->id)->where('jalur_pendaftaran_id', $jalur->id)->firstOrFail();
    $kuota->update(['kuota' => 1, 'terisi' => 1, 'kuota_pendaftaran' => null, 'terisi_pendaftaran' => 99]);

    $this->post(route('spmb.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'gelombang_id' => gelombangTerbukaId(),
        'nama_lengkap' => 'Tanpa Batas',
        'jenis_kelamin' => 'L',
        'nik' => '1234567890123700',
        'nisn' => '1234567370',
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-05-10',
        'agama' => 'Islam',
        'asal_sekolah' => 'SMP 1',
        'alamat' => 'Jl Tanpa Batas',
        'no_hp' => '081234567890',
        'email' => 'tanpa-batas@example.com',
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
    ])->assertSessionHasNoErrors();

    expect($kuota->fresh()->terisi_pendaftaran)->toBe(100);
});

test('pendaftaran ditolak sebelum gelombang dibuka', function () {
    Gelombang::query()->delete();
    Gelombang::create([
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'nama_gelombang' => 'Belum Mulai',
        'nomor_urut' => 90,
        'kuota' => 30,
        'is_aktif' => true,
    ])->tahapan()->create([
        'tipe' => 'pendaftaran',
        'nama_tahap' => 'Pendaftaran Online',
        'urutan' => 1,
        'tanggal_mulai' => now()->addWeek()->toDateString(),
        'tanggal_selesai' => now()->addMonth()->toDateString(),
    ]);

    $this->post(route('spmb.store'), payloadSpmb([
        'nik' => '1234567890123800',
        'nisn' => '1234567380',
        'email' => 'belum@example.com',
    ]))->assertSessionHasErrors('gelombang_id');

    expect(CalonSiswa::where('nik', '1234567890123800')->exists())->toBeFalse();
});

test('pendaftaran ditolak setelah semua gelombang lewat', function () {
    Gelombang::query()->delete();
    Gelombang::create([
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'nama_gelombang' => 'Sudah Lewat',
        'nomor_urut' => 90,
        'kuota' => 30,
        'is_aktif' => true,
    ])->tahapan()->create([
        'tipe' => 'pendaftaran',
        'nama_tahap' => 'Pendaftaran Online',
        'urutan' => 1,
        'tanggal_mulai' => now()->subMonths(2)->toDateString(),
        'tanggal_selesai' => now()->subDay()->toDateString(),
    ]);

    // Tanpa memilih gelombang pun harus ditolak: pendaftaran yang sudah
    // tutup tidak boleh lolos hanya karena field-nya tidak diisi.
    $this->post(route('spmb.store'), payloadSpmb([
        'nik' => '1234567890123900',
        'nisn' => '1234567390',
        'email' => 'selesai@example.com',
        'gelombang_id' => null,
    ]))->assertSessionHasErrors('gelombang_id');

    expect(CalonSiswa::where('nik', '1234567890123900')->exists())->toBeFalse();
});

test('tanpa gelombang, pendaftaran tetap dibuka', function () {
    // Sekolah yang belum mengatur batch tidak boleh terkunci.
    Gelombang::query()->delete();

    $this->post(route('spmb.store'), payloadSpmb([
        'nik' => '1234567890124000',
        'nisn' => '1234567400',
        'email' => 'tanpa-jadwal@example.com',
    ]))->assertSessionHasNoErrors();

    expect(CalonSiswa::where('nik', '1234567890124000')->exists())->toBeTrue();
});

test('kuota gelombang penuh menutup pendaftaran', function () {
    Gelombang::query()->delete();
    Gelombang::create([
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'nama_gelombang' => 'Penuh',
        'nomor_urut' => 90,
        'kuota' => 5,
        'terisi' => 5,
        'is_aktif' => true,
    ])->tahapan()->create([
        'tipe' => 'pendaftaran',
        'nama_tahap' => 'Pendaftaran Online',
        'urutan' => 1,
        'tanggal_mulai' => now()->subWeek()->toDateString(),
        'tanggal_selesai' => now()->addWeek()->toDateString(),
    ]);

    $this->post(route('spmb.store'), payloadSpmb([
        'nik' => '1234567890124100',
        'nisn' => '1234567410',
        'email' => 'penuh@example.com',
    ]))->assertSessionHasErrors('gelombang_id');

    expect(CalonSiswa::where('nik', '1234567890124100')->exists())->toBeFalse();
});

test('halaman pendaftaran menampilkan peringatan ketika ditutup', function () {
    Gelombang::query()->delete();
    Gelombang::create([
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'nama_gelombang' => 'Akan Segera',
        'nomor_urut' => 90,
        'kuota' => 30,
        'is_aktif' => true,
    ])->tahapan()->create([
        'tipe' => 'pendaftaran',
        'nama_tahap' => 'Pendaftaran Online',
        'urutan' => 1,
        'tanggal_mulai' => now()->addWeek()->toDateString(),
        'tanggal_selesai' => now()->addMonth()->toDateString(),
    ]);

    $this->get(route('spmb.pendaftaran'))
        ->assertOk()
        ->assertSee('Pendaftaran Belum Dibuka')
        // Pesan menyebut batch dan tanggalnya, bukan tanggal global.
        ->assertSee('Akan Segera');
});

test('wizard menampilkan sisa kuota pendaftaran dari database', function () {
    $kuota = KuotaPendaftaran::whereNotNull('kuota_pendaftaran')->firstOrFail();
    $jalur = JalurPendaftaran::findOrFail($kuota->jalur_pendaftaran_id);
    $sisa = $kuota->kuota_pendaftaran - $kuota->terisi_pendaftaran;

    $html = $this->get(route('spmb.pendaftaran'))->assertOk()->getContent();

    // Sisa kuota ikut tampil di nama opsi (dari $kuotaMap yang sebelumnya mati).
    expect($html)->toContain($jalur->nama_jalur.' — sisa '.$sisa.' kursi')
        ->and($html)->toContain('data-sisa="'.$sisa.'"')
        ->and($html)->toContain('data-kapasitas="'.$kuota->kuota_pendaftaran.'"')
        ->and($html)->toContain('info-kuota');
});

test('jalur tanpa batas kuota tidak menampilkan angka sisa', function () {
    $jalur = JalurPendaftaran::firstOrFail();
    KuotaPendaftaran::where('jalur_pendaftaran_id', $jalur->id)
        ->update(['kuota_pendaftaran' => null]);

    $html = $this->get(route('spmb.pendaftaran'))->assertOk()->getContent();

    // `data-kapasitas` kosong = tanpa batas; opsi tetap bisa dipilih.
    expect($html)->toContain('data-sisa=""')
        ->and($html)->not->toContain($jalur->nama_jalur.' — Penuh')
        ->and($html)->not->toContain($jalur->nama_jalur.' — sisa');
});

test('jalur yang kuotanya penuh otomatis dinonaktifkan di wizard', function () {
    $kuota = KuotaPendaftaran::whereNotNull('kuota_pendaftaran')->firstOrFail();
    $jalur = JalurPendaftaran::findOrFail($kuota->jalur_pendaftaran_id);
    $kuota->update(['terisi_pendaftaran' => $kuota->kuota_pendaftaran]);

    $html = $this->get(route('spmb.pendaftaran'))->assertOk()->getContent();

    expect($html)->toContain($jalur->nama_jalur.' — Penuh')
        ->and($html)->toContain('disabled');
});

test('pendaftaran publik dibatasi 20 percobaan per menit per IP', function () {
    // Batas lama 5/menit terlalu kecil untuk sekolah yang berbagi IP/NAT.
    $payload = payloadSpmb(['nik' => '1234567890124300', 'nisn' => '1234567430']);

    for ($i = 1; $i <= 20; $i++) {
        $this->post(route('spmb.store'), $payload)->assertRedirect();
    }

    // Percobaan ke-21 diblokir. Respons throttle ikut membawa pesan yang bisa
    // dibaca pengguna (bukan 429 kosong).
    $response = $this->post(route('spmb.store'), $payload);
    $response->assertRedirect();
    $response->assertSessionHasErrors('jalur_pendaftaran_id');

    $pesan = implode(' ', session('errors')->getBag('default')->all());
    expect($pesan)->toContain('Terlalu banyak permintaan pendaftaran');
});

test('pendaftaran ke jalur non-aktif ditolak walau POST langsung', function () {
    $jalur = JalurPendaftaran::firstOrFail();
    $jalur->update(['aktif' => false]);

    $this->post(route('spmb.store'), payloadSpmb([
        'jalur_pendaftaran_id' => $jalur->id,
        'nik' => '1234567890124200',
        'nisn' => '1234567420',
        'email' => 'jalur-nonaktif@example.com',
    ]))->assertSessionHasErrors('jalur_pendaftaran_id');

    expect(CalonSiswa::where('nik', '1234567890124200')->exists())->toBeFalse();

    // Jalur non-aktif tidak boleh muncul sebagai pilihan di wizard.
    $this->get(route('spmb.pendaftaran'))
        ->assertOk()
        ->assertDontSee('>'.$jalur->id.'" data-wajib-sertifikat', escape: false);
});

test('agama pada form publik memakai pilihan baku yang sama dengan admin', function () {
    $html = $this->get(route('spmb.pendaftaran'))->assertOk()->getContent();

    // Form publik harus menawarkan select, bukan free-text: nilai seperti
    // "islam" kecil pernah gagal cocok saat admin mengedit kandidat.
    foreach (CalonSiswa::AGAMA as $agama) {
        expect($html)->toContain('>'.$agama.'</option>');
    }

    // Nilai di luar daftar baku ditolak, bukan disimpan bebas.
    $this->post(route('spmb.store'), payloadSpmb([
        'nik' => '1234567890124100',
        'nisn' => '1234567410',
        'email' => 'agama-free@example.com',
        'agama' => 'islam',
    ]))->assertSessionHasErrors('agama');

    expect(CalonSiswa::where('nik', '1234567890124100')->exists())->toBeFalse();
});

test('halaman publik tidak lagi menjanjikan pembuatan akun terpisah', function () {
    $html = $this->get(route('spmb.home'))->assertOk()->getContent();

    // Tidak ada form password di wizard, jadi jangan menjanjikan langkah
    // "buat akun dengan email dan password" seperti dulu.
    expect($html)->not->toContain('Buat Akun')
        ->and($html)->not->toContain('dengan email dan password')
        ->and($html)->not->toContain('membuat akun')
        // Penjelasan baru: akun terbit otomatis, username = NISN.
        ->and($html)->toContain('dibuat otomatis')
        ->and($html)->toContain('username memakai NISN');
});

test('halaman publik tidak lagi menjanjikan batas 2MB untuk dokumen PDF', function () {
    $html = $this->get(route('spmb.home'))->assertOk()->getContent();

    // Validasi menerima PDF sampai 5MB; teks lama "max 2MB" menyesatkan.
    expect($html)->not->toContain('max 2MB')
        ->and($html)->toContain('dokumen PDF maks 5MB')
        ->and($html)->toContain('pas foto JPG/PNG maks 2MB');
});

test('jadwal hasil seeder membuka satu gelombang dan punya tahap berurutan', function () {
    $tersedia = Gelombang::terbuka(TahunAjaran::aktif()->first());

    // Gate pendaftaran sekarang hanya dari Gelombang.
    expect($tersedia)->not->toBeEmpty()
        ->and($tersedia->first()->bisaMasuk())->toBeTrue();

    // Tahapan harus berurutan kronologis. Ini penjaga dari regresi: versi lama
    // menghitung tanggal tes dari tanggal BUKA, sehingga Gelombang 1 yang baru
    // dibuka punya tes di masa lalu.
    foreach ($tersedia as $gelombang) {
        $sebelumnya = null;

        foreach ($gelombang->tahapan as $tahap) {
            if ($sebelumnya !== null) {
                expect($tahap->tanggal_mulai->toDateString())
                    ->not->toBeLessThan($sebelumnya);
            }

            $sebelumnya = $tahap->tanggal_mulai->toDateString();
        }
    }
});

test('tidak ada tahap yang jatuh sebelum pendaftaran gelombang dibuka', function () {
    foreach (Gelombang::with('tahapan')->get() as $gelombang) {
        $pendaftaran = $gelombang->tahapan->firstWhere('tipe', 'pendaftaran');

        expect($pendaftaran)->not->toBeNull();

        foreach ($gelombang->tahapan->where('tipe', '!=', 'pendaftaran') as $tahap) {
            expect($tahap->tanggal_mulai->toDateString())
                ->not->toBeLessThan($pendaftaran->tanggal_mulai->toDateString());
        }
    }
});

test('seeder mengisi kuota pendaftaran dan kuota penerimaan terpisah', function () {
    $kuota = KuotaPendaftaran::whereNotNull('kuota_pendaftaran')->firstOrFail();

    expect($kuota->terisi_pendaftaran)->toBeGreaterThan(0)
        ->and($kuota->kuota)->toBeGreaterThan(0)
        ->and($kuota->pendaftaranPenuh())->toBeFalse()
        ->and($kuota->penerimaanPenuh())->toBeFalse();
});

test('email yang sudah dipakai akun lain ditolak tanpa membocorkan pesan SQL', function () {
    $jalur = JalurPendaftaran::first();
    // Akun admin/guru sudah memakai email ini; email juga UNIQUE di tabel users.
    User::factory()->create(['email' => 'terpakai@example.com']);

    $response = $this->post(route('spmb.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'nama_lengkap' => 'Email Bentrok',
        'jenis_kelamin' => 'L',
        'nik' => '1234567890123400',
        'nisn' => '1234567800',
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-05-10',
        'agama' => 'Islam',
        'asal_sekolah' => 'SMP 1',
        'alamat' => 'Jl Bentrok 1',
        'no_hp' => '081234567890',
        'email' => 'terpakai@example.com',
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
    ]);

    $response->assertSessionHasErrors('email');
    $errors = session('errors')->getBag('default')->all();
    $pesan = implode(' ', $errors);

    // Pesan ramah, bukan SQL mentah yang membocorkan detail database.
    expect($pesan)->toContain('email')
        ->and(strtolower($pesan))->not->toContain('sqlstate')
        ->and(strtolower($pesan))->not->toContain('insert into')
        ->and(strtolower($pesan))->not->toContain('unique constraint');

    // Tidak ada akun/calon yang bocor dari percobaan yang gagal.
    expect(CalonSiswa::where('nik', '1234567890123400')->exists())->toBeFalse();
});

test('nisn yang sudah jadi username akun lain ditolak pada field nisn', function () {
    $jalur = JalurPendaftaran::first();
    User::factory()->create(['username' => '1234567899', 'email' => 'lain@example.com']);

    $response = $this->post(route('spmb.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'nama_lengkap' => 'Nisn Bentrok',
        'jenis_kelamin' => 'P',
        'nik' => '1234567890123401',
        'nisn' => '1234567899',
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-05-10',
        'agama' => 'Islam',
        'asal_sekolah' => 'SMP 1',
        'alamat' => 'Jl Bentrok 2',
        'no_hp' => '081234567890',
        'email' => 'nisn-bentrok@example.com',
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
    ]);

    $response->assertSessionHasErrors('nisn');
    expect(CalonSiswa::where('nik', '1234567890123401')->exists())->toBeFalse();
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
    $kuota->update(['kuota_pendaftaran' => 1, 'terisi_pendaftaran' => 1]);

    $response = $this->post(route('spmb.store'), [
        'jalur_pendaftaran_id' => $jalur->id,
        'gelombang_id' => gelombangTerbukaId(),
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

/**
 * Isi hanya bagian hero landing page.
 *
 * Assertion atas seluruh halaman tidak bisa membedakan badge hero dari teks
 * lain yang kebetulan sama (mis. label "Pendaftaran Dibuka" di kartu gelombang,
 * atau tahun ajaran yang ikut muncul di isi pengumuman hasil seed).
 */
function heroHtml(string $html): string
{
    preg_match('/<section id="beranda".*?<\/section>/s', $html, $m);

    return $m[0] ?? '';
}

test('kartu kuota terbatas memakai angka nyata dari database, bukan hardcoded', function () {
    $tahun = TahunAjaran::aktif()->firstOrFail();

    // Semua jalur berbatas diseragamkan; jalur tanpa batas (NULL) tidak boleh
    // ikut dihitung. Ekspektasi dihitung dari database, bukan angka tetap,
    // supaya test tidak bergantung pada jumlah baris hasil seeder.
    $berbatas = KuotaPendaftaran::where('tahun_ajaran_id', $tahun->id)
        ->whereNotNull('kuota_pendaftaran')
        ->get();
    $tanpaBatas = KuotaPendaftaran::where('tahun_ajaran_id', $tahun->id)
        ->whereNull('kuota_pendaftaran')
        ->get();

    expect($berbatas)->not->toBeEmpty();
    $berbatas->each(fn ($k) => $k->update(['kuota_pendaftaran' => 100, 'terisi_pendaftaran' => 25]));
    $tanpaBatas->each(fn ($k) => $k->update(['terisi_pendaftaran' => 999]));

    $kapasitas = $berbatas->count() * 100;
    $terisi = $berbatas->count() * 25;
    $persen = (int) round($terisi / $kapasitas * 100);

    $html = $this->get(route('spmb.home'))->assertOk()->getContent();

    expect($html)->toContain('>'.$kapasitas.'</span>')
        ->and($html)->toContain('width: '.$persen.'%')
        ->and($html)->toContain($persen.'% kuota pendaftaran terisi')
        ->and($html)->toContain($terisi.'/'.$kapasitas.' terisi')
        // Jalur tanpa batas tidak boleh menambah kapasitas maupun terisi.
        ->and($html)->not->toContain('>'.($kapasitas + 999).'</span>');
});

test('kartu kuota terbatas tidak menampilkan angka palsu saat belum ada data', function () {
    TahunAjaran::query()->update(['status_aktif' => false]);
    KuotaPendaftaran::query()->delete();

    $html = $this->get(route('spmb.home'))->assertOk()->getContent();

    // Angka lama (180 siswa / 6 kelas / 45%) tidak boleh muncul sebagai
    // cadangan ketika sumber datanya kosong.
    expect($html)->not->toContain('>180</span>')
        ->and($html)->not->toContain('6 Kelas Tersedia')
        ->and($html)->toContain('—</span>');
});

test('statistik hero memakai jumlah siswa dan guru aktif dari database', function () {
    $hero = heroHtml($this->get(route('spmb.home'))->assertOk()->getContent());

    $siswa = Siswa::where('is_aktif', true)->count();
    $guru = Guru::where('is_aktif', true)->count();

    expect($hero)->toContain('>'.($siswa ?: '—').'</div>')
        ->and($hero)->toContain('>'.($guru ?: '—').'</div>')
        // Angka karangan lama harus hilang.
        ->and($hero)->not->toContain('500+')
        ->and($hero)->not->toContain('25+')
        ->and($hero)->not->toContain('15+');
});

test('tahun ajaran pada hero diambil dari tahun ajaran aktif', function () {
    $tahun = TahunAjaran::aktif()->firstOrFail();
    $tahun->update(['nama_tahun_ajaran' => '2099/2100']);

    $hero = heroHtml($this->get(route('spmb.home'))->assertOk()->getContent());

    expect($hero)->toContain('2099/2100')
        // Literal lama tidak boleh lagi ditulis di view hero.
        ->and($hero)->not->toContain('2026/2027');
});

test('badge hero mencerminkan status pendaftaran dari gelombang', function () {
    // Dibuka: badge hijau.
    $hero = heroHtml($this->get(route('spmb.home'))->assertOk()->getContent());
    expect($hero)->toContain('Pendaftaran Dibuka!');

    // Belum mulai: badge menyebut batch berikutnya, bukan "Dibuka!".
    Gelombang::query()->delete();
    Gelombang::create([
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'nama_gelombang' => 'Gelombang Mendatang',
        'nomor_urut' => 90,
        'kuota' => 30,
        'is_aktif' => true,
    ])->tahapan()->create([
        'tipe' => 'pendaftaran',
        'nama_tahap' => 'Pendaftaran Online',
        'urutan' => 1,
        'tanggal_mulai' => now()->addWeek()->toDateString(),
        'tanggal_selesai' => now()->addMonth()->toDateString(),
    ]);

    $mulai = now()->addWeek()->translatedFormat('d M Y');
    $hero = heroHtml($this->get(route('spmb.home'))->assertOk()->getContent());

    expect($hero)->toContain('Pendaftaran dibuka '.$mulai)
        ->and($hero)->toContain('Gelombang Mendatang')
        ->and($hero)->not->toContain('Pendaftaran Dibuka!');
});

test('badge hero menampilkan pendaftaran ditutup setelah semua gelombang lewat', function () {
    Gelombang::query()->delete();
    Gelombang::create([
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'nama_gelombang' => 'Gelombang Lewat',
        'nomor_urut' => 90,
        'kuota' => 30,
        'is_aktif' => true,
    ])->tahapan()->create([
        'tipe' => 'pendaftaran',
        'nama_tahap' => 'Pendaftaran Online',
        'urutan' => 1,
        'tanggal_mulai' => now()->subMonths(2)->toDateString(),
        'tanggal_selesai' => now()->subDay()->toDateString(),
    ]);

    $hero = heroHtml($this->get(route('spmb.home'))->assertOk()->getContent());

    expect($hero)->toContain('Pendaftaran Ditutup')
        ->and($hero)->not->toContain('Pendaftaran Dibuka!');
});

test('badge hero menampilkan kuota penuh saat semua gelombang yang terbuka penuh', function () {
    Gelombang::query()->delete();
    Gelombang::create([
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'nama_gelombang' => 'Gelombang Penuh',
        'nomor_urut' => 90,
        'kuota' => 5,
        'terisi' => 5,
        'is_aktif' => true,
    ])->tahapan()->create([
        'tipe' => 'pendaftaran',
        'nama_tahap' => 'Pendaftaran Online',
        'urutan' => 1,
        'tanggal_mulai' => now()->subWeek()->toDateString(),
        'tanggal_selesai' => now()->addWeek()->toDateString(),
    ]);

    $hero = heroHtml($this->get(route('spmb.home'))->assertOk()->getContent());

    expect($hero)->toContain('Kuota Gelombang Penuh')
        ->and($hero)->not->toContain('Pendaftaran Dibuka!')
        // Dan form-nya benar-benar tertutup, bukan cuma badge-nya.
        ->and($this->get(route('spmb.pendaftaran'))->assertOk()->getContent())
        ->toContain('Kuota Gelombang Penuh');
});

test('badge hero disembunyikan ketika sekolah belum punya gelombang', function () {
    // Tanpa batch, tidak ada yang bisa dijanjikan - tapi pendaftaran tetap
    // dibuka supaya sekolah tidak terkunci.
    Gelombang::query()->delete();

    $hero = heroHtml($this->get(route('spmb.home'))->assertOk()->getContent());

    expect($hero)->not->toContain('Pendaftaran Dibuka')
        ->and($hero)->not->toContain('Pendaftaran Ditutup')
        ->and($hero)->not->toContain('Kuota Gelombang Penuh');
});

test('badge hero disembunyikan ketika tidak ada tahun ajaran aktif', function () {
    TahunAjaran::query()->update(['status_aktif' => false]);

    $hero = heroHtml($this->get(route('spmb.home'))->assertOk()->getContent());

    // Tanpa tahun ajaran tidak ada yang bisa dijanjikan, jadi jangan tampilkan
    // status pendaftaran sama sekali.
    expect($hero)->not->toContain('Pendaftaran Dibuka')
        ->and($hero)->not->toContain('Pendaftaran Ditutup');
});

test('placeholder GANTI pada profil tidak bocor ke halaman publik', function () {
    // Nilai persis seperti hasil seeder: masih placeholder.
    ProfilSekolah::create([
        'nama_sekolah' => 'SMP PIRI NGAGLIK',
        'alamat' => 'GANTI: Jl. ... , Ngaglik, Sleman, Yogyakarta',
        'telp' => 'GANTI: (0274) ...',
        'sambutan' => 'GANTI: sambutan kepala sekolah untuk halaman tentang kami.',
        'visi' => 'GANTI: visi sekolah.',
        'nama_kepala' => 'GANTI: Nama Kepala Sekolah',
    ]);

    foreach (['spmb.home', 'spmb.pendaftaran', 'spmb.about'] as $route) {
        $html = $this->get(route($route))->assertOk()->getContent();

        expect($html)->not->toContain('GANTI:')
            // `href="tel:GANTI: ..."` dulu benar-benar merusak tautan telepon.
            ->and($html)->not->toContain('tel:GANTI');
    }
});

test('data kontak palsu bawaan template tidak lagi tampil di halaman publik', function () {
    ProfilSekolah::create([
        'nama_sekolah' => 'SMP PIRI NGAGLIK',
        'alamat' => 'GANTI: Jl. ... , Ngaglik',
        'telp' => 'GANTI: (0274) ...',
    ]);

    foreach (['spmb.home', 'spmb.pendaftaran', 'spmb.about'] as $route) {
        $html = $this->get(route($route))->assertOk()->getContent();

        // Nomor & alamat contoh dari template awal bukan milik sekolah mana pun.
        expect($html)->not->toContain('Kelurahan Maju Jaya')
            ->and($html)->not->toContain('smpharapanbangsa.sch.id')
            ->and($html)->not->toContain('0812-3456-7890')
            ->and($html)->not->toContain('(022) 1234-5678');
    }
});

test('accessor profil menyaring placeholder dan kosong', function () {
    $profil = ProfilSekolah::create([
        'nama_sekolah' => 'SMP PIRI NGAGLIK',
        'alamat' => 'GANTI: Jl. ...',
        'telp' => '  ',
        'email' => 'spmb@piri.example.sch.id',
        'visi' => 'TODO: visi',
        'nama_kepala' => 'Budi Santoso, S.Pd.',
        'misi' => ['Misi pertama', 'GANTI: misi kedua', '', 'Misi ketiga'],
    ]);

    expect($profil->alamat_bersih)->toBeNull()
        ->and($profil->telp_bersih)->toBeNull()
        ->and($profil->telp_tel)->toBeNull()
        ->and($profil->whatsapp)->toBeNull()
        ->and($profil->visi_bersih)->toBeNull()
        ->and($profil->email_bersih)->toBe('spmb@piri.example.sch.id')
        ->and($profil->nama_kepala_bersih)->toBe('Budi Santoso, S.Pd.')
        // Entri misi yang kosong / placeholder ikut dibuang.
        ->and($profil->misi_bersih)->toBe(['Misi pertama', 'Misi ketiga']);
});

test('nomor telepon profil dinormalkan untuk tautan tel dan whatsapp', function () {
    $profil = ProfilSekolah::create([
        'nama_sekolah' => 'SMP PIRI NGAGLIK',
        'telp' => '(0274) 123 456',
    ]);

    // 0274 -> +62 274, wa.me tanpa kode negara.
    expect($profil->telp_tel)->toBe('+62274123456')
        ->and($profil->whatsapp)->toBe('62274123456')
        ->and($profil->telp_bersih)->toBe('(0274) 123 456');
});

test('visi misi dan sambutan karangan tidak lagi muncul di halaman tentang', function () {
    ProfilSekolah::create([
        'nama_sekolah' => 'SMP PIRI NGAGLIK',
        'visi' => 'GANTI: visi sekolah.',
        'misi' => [],
        'nama_kepala' => 'GANTI: Nama Kepala Sekolah',
    ]);

    $html = $this->get(route('spmb.about'))->assertOk()->getContent();

    // Versi lama mengarang lima butir misi, satu visi, dan nama kepala sekolah
    // lengkap dengan gelar.
    expect($html)->not->toContain('Dr. Ahmad Fauzi')
        ->and($html)->not->toContain('Menyelenggarakan pembelajaran yang aktif')
        ->and($html)->not->toContain('Mewujudkan generasi yang cerdas')
        ->and($html)->not->toContain('Ahmad Fauzi');
});

test('visi misi dan sambutan profil yang sudah terisi tetap tampil', function () {
    ProfilSekolah::create([
        'nama_sekolah' => 'SMP PIRI NGAGLIK',
        'visi' => 'Visi resmi sekolah',
        'misi' => ['Misi resmi pertama', 'Misi resmi kedua'],
        'nama_kepala' => 'Budi Santoso, S.Pd.',
        'sambutan' => 'Sambutan resmi kepala sekolah.',
    ]);

    $html = $this->get(route('spmb.about'))->assertOk()->getContent();

    expect($html)->toContain('Visi resmi sekolah')
        ->and($html)->toContain('Misi resmi pertama')
        ->and($html)->toContain('Misi resmi kedua')
        ->and($html)->toContain('Budi Santoso, S.Pd.')
        ->and($html)->toContain('Sambutan resmi kepala sekolah.');
});

test('kartu ekstrakurikuler di landing page membaca data yang benar-benar ada', function () {
    Ekstrakurikuler::query()->delete();

    Ekstrakurikuler::create([
        'kode' => 'PMR',
        'nama' => 'Palang Merah Remaja',
        'jadwal' => 'Kamis 15:00-16:30',
        'deskripsi' => 'Latihan pertolongan pertama.',
        'is_aktif' => true,
    ]);

    Ekstrakurikuler::create([
        'kode' => 'ROHIS',
        'nama' => 'Rohani Islam',
        'is_aktif' => true,
    ]);

    Ekstrakurikuler::create([
        'kode' => 'LAMA',
        'nama' => 'Klub Sudah Dimatikan',
        'is_aktif' => false,
    ]);

    $html = $this->get(route('spmb.home'))->assertOk()->getContent();

    // Yang tampil harus yang ada di database.
    expect($html)->toContain('Palang Merah Remaja')
        ->and($html)->toContain('Latihan pertolongan pertama.')
        ->and($html)->toContain('Kamis 15:00-16:30')
        ->and($html)->toContain('Rohani Islam');

    // Baris non-aktif tidak boleh diiklankan.
    expect($html)->not->toContain('Klub Sudah Dimatikan');

    // Sembilan kartu versi lama hampir semuanya tidak punya baris di tabel,
    // jadi tidak boleh muncul sebagai kegiatan yang diiklankan.
    foreach (['Basket', 'English Club', 'Robotika &amp; Coding', 'Seni Musik', 'Tari Tradisional', 'Jurnalistik'] as $karangan) {
        expect($html)->not->toContain($karangan);
    }
});

test('landing page menampilkan empty state saat belum ada ekstrakurikuler', function () {
    Ekstrakurikuler::query()->delete();

    $html = $this->get(route('spmb.home'))->assertOk()->getContent();

    expect($html)->toContain('Belum ada kegiatan ekstrakurikuler');
});

test('nama sekolah konsisten di semua halaman publik', function () {
    ProfilSekolah::create(['nama_sekolah' => 'SMP PIRI NGAGLIK']);

    foreach (['spmb.home', 'spmb.about', 'spmb.pendaftaran'] as $route) {
        $html = $this->get(route($route))->assertOk()->getContent();

        expect($html)->toContain('SMP PIRI NGAGLIK')
            // Nama sekolah lama tidak boleh muncul di mana pun.
            ->and($html)->not->toContain('SMKN Ngaglik')
            ->and($html)->not->toContain('SMP Harapan Bangsa')
            ->and($html)->not->toContain('SMK Negeri 1 Ngaglik');
    }
});

test('halaman publik tidak mencantumkan nama sekolah yang belum diisi', function () {
    // Tanpa baris profil sama sekali.
    expect(ProfilSekolah::count())->toBe(0);

    foreach (['spmb.home', 'spmb.about', 'spmb.pendaftaran'] as $route) {
        $html = $this->get(route($route))->assertOk()->getContent();

        expect($html)->not->toContain('SMKN')
            ->and($html)->not->toContain('Harapan Bangsa');
    }
});

test('seeder profil memakai nama resmi sekolah dan tidak mengarang email', function () {
    $this->seed(ProfilSekolahSeeder::class);

    $profil = ProfilSekolah::aktif();

    expect($profil)->not->toBeNull()
        ->and($profil->nama_sekolah)->toBe('SMP PIRI NGAGLIK')
        // Domain sekolah tidak ada di repo; menebaknya bisa membuat alamat
        // email yang mengarah ke pihak lain.
        ->and($profil->email)->toBeNull()
        ->and($profil->alamat_bersih)->toBeNull()
        ->and($profil->telp_bersih)->toBeNull();
});

test('halaman edit profil tidak membuat baris profil kedua setelah nama diganti', function () {
    $admin = User::factory()->create();
    // Permission sudah dibuat PermissionSeeder di beforeEach. GET butuh
    // permission .view, PUT butuh .edit.
    $admin->givePermissionTo(['profil-sekolahs.view', 'profil-sekolahs.edit']);

    // Baris pertama sudah punya nama lain (mis. hasil seed lama).
    ProfilSekolah::create(['nama_sekolah' => 'SMKN Ngaglik']);

    $this->actingAs($admin)->get(route('admin.profil-sekolah.edit'))->assertOk();

    expect(ProfilSekolah::count())->toBe(1);

    // Admin mengganti nama sekolah, lalu form dibuka lagi. Versi lama memakai
    // nama sekolah sebagai kunci firstOrCreate sehingga muncul baris kedua.
    ProfilSekolah::aktif()->update(['nama_sekolah' => 'SMP PIRI NGAGLIK']);

    $this->actingAs($admin)->get(route('admin.profil-sekolah.edit'))->assertOk();

    expect(ProfilSekolah::count())->toBe(1);
});
