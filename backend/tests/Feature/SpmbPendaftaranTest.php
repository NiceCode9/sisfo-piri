<?php

use App\Models\CalonSiswa;
use App\Models\JadwalPpdb;
use App\Models\JalurPendaftaran;
use App\Models\KuotaPendaftaran;
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

    // Jadwal seed bertanggal Mei 2026 (sudah lewat). Gate jadwal memakai
    // rentang ini, jadi dibuat melatari "hari ini" supaya test tidak bergantung
    // pada kalender. Test khusus gate mengatur rentangnya sendiri.
    JadwalPpdb::where('tipe', 'pendaftaran')->update([
        'tanggal_mulai' => now()->subMonth()->toDateString(),
        'tanggal_selesai' => now()->addMonth()->toDateString(),
    ]);
});

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

test('pendaftaran ditolak sebelum jadwal pendaftaran dimulai', function () {
    JadwalPpdb::where('tipe', 'pendaftaran')->update([
        'tanggal_mulai' => now()->addWeek()->toDateString(),
        'tanggal_selesai' => now()->addMonth()->toDateString(),
    ]);

    $this->post(route('spmb.store'), payloadSpmb([
        'nik' => '1234567890123800',
        'nisn' => '1234567380',
        'email' => 'belum@example.com',
    ]))->assertSessionHasErrors('jalur_pendaftaran_id');

    expect(CalonSiswa::where('nik', '1234567890123800')->exists())->toBeFalse();
});

test('pendaftaran ditolak setelah jadwal pendaftaran berakhir', function () {
    JadwalPpdb::where('tipe', 'pendaftaran')->update([
        'tanggal_mulai' => now()->subMonths(2)->toDateString(),
        'tanggal_selesai' => now()->subDay()->toDateString(),
    ]);

    $this->post(route('spmb.store'), payloadSpmb([
        'nik' => '1234567890123900',
        'nisn' => '1234567390',
        'email' => 'selesai@example.com',
    ]))->assertSessionHasErrors('jalur_pendaftaran_id');

    expect(CalonSiswa::where('nik', '1234567890123900')->exists())->toBeFalse();
});

test('tanpa baris jadwal bertipe pendaftaran, pendaftaran tetap dibuka', function () {
    JadwalPpdb::query()->delete();

    $this->post(route('spmb.store'), payloadSpmb([
        'nik' => '1234567890124000',
        'nisn' => '1234567400',
        'email' => 'tanpa-jadwal@example.com',
    ]))->assertSessionHasNoErrors();

    expect(CalonSiswa::where('nik', '1234567890124000')->exists())->toBeTrue();
});

test('halaman pendaftaran menampilkan peringatan ketika ditutup', function () {
    JadwalPpdb::where('tipe', 'pendaftaran')->update([
        'tanggal_mulai' => now()->addWeek()->toDateString(),
        'tanggal_selesai' => now()->addMonth()->toDateString(),
    ]);

    $this->get(route('spmb.pendaftaran'))
        ->assertOk()
        ->assertSee('Pendaftaran Belum Dibuka');
});

test('timeline pendaftaran memakai data JadwalPpdb dari database', function () {
    $this->get(route('spmb.pendaftaran'))
        ->assertOk()
        // Nama & tanggal asli dari seeder harus tampil.
        ->assertSee('Pendaftaran Online')
        ->assertSee('Verifikasi Berkas')
        ->assertSee('Tes Seleksi')
        ->assertSee('Daftar Ulang')
        // Tanggal dari database, bukan dummy hardcoded. Jadwal non-pendaftaran
        // masih memakai tanggal seed; baris pendaftaran sudah di-override beforeEach.
        ->assertSee('15 Jun 2026')
        ->assertSee('30 Jun 2026')
        ->assertSee(now()->subMonth()->translatedFormat('d M Y'))
        // Tanggal dummy lama (Tes Masuk 5 Des, Verifikasi 12 Des) tidak boleh muncul.
        ->assertDontSee('Tes Masuk')
        ->assertDontSee('5 Des 2026')
        ->assertDontSee('12 Des 2026')
        ->assertDontSee('21 Des 2026');
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
