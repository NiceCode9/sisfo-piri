<?php

/**
 * Kartu izin/sakit di halaman QR Absen guru (spek 2026-10-23).
 *
 * Penjagaan yurisdiksi lebih menentukan di sini daripada formnya sendiri:
 * satu endpoint tulis-baris-manual yang tidak menuntut `absensis.create`
 * harus membuktikan sendiri bahwa ia hanya menyentuh rombel kecil guru itu.
 */

use App\Models\Absensi;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
});

if (! function_exists('rombelIzin')) {
    /** Rombel 7A uji dari seeder, satu angka dengan rombelQrMandiri file lain. */
    function rombelIzin(): Rombel
    {
        return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();
    }
}

if (! function_exists('rombelIzinLain')) {
    /** Rombeling yang bukan milik guru uji mana pun. */
    function rombelIzinLain(string $namaKelas = '7B'): Rombel
    {
        $kelas = Kelas::factory()->create(['nama_kelas' => $namaKelas]);

        return Rombel::factory()->create(['kelas_id' => $kelas->id, 'wali_guru_id' => null]);
    }
}

if (! function_exists('siswaIzin')) {
    /**
     * Siswa aktif di rombel — pola siswaQrMandiri, tapi mandiri agar file ini
     * bisa dijalankan sendiri dengan `vendor/bin/pest tests/Feature/...`.
     */
    function siswaIzin(string $nis, string $nama, ?Rombel $rombel = null): Siswa
    {
        static $n = 0;
        $n++;

        $rombel ??= rombelIzin();

        $user = User::factory()->create(['username' => 'siswa-izin-'.$n.'-'.str()->random(4), 'name' => $nama]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'nisn' => sprintf('008030%04d', $n),
            'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
            'kelas_id' => $rombel->kelas_id,
            'is_aktif' => true,
        ]);

        RiwayatKelas::create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $rombel->kelas_id,
            'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
            'status' => 'aktif',
        ]);

        return $siswa;
    }
}

if (! function_exists('guruKartuIzin')) {
    /** Guru yang mengampu mapel di $rombel ($rombel null = tanpa penugasan). */
    function guruKartuIzin(?Rombel $rombel = null): User
    {
        static $n = 0;
        $n++;

        $user = User::factory()->create(['username' => 'guru-izin-'.$n.'-'.str()->random(4), 'name' => 'Guru Kartu '.$n]);
        $user->assignRole('guru');

        $guru = Guru::create([
            'user_id' => $user->id,
            'nama' => $user->name,
            'jenis_kelamin' => 'L',
            'is_aktif' => true,
        ]);

        if ($rombel !== null) {
            Pengampu::create([
                'guru_id' => $guru->id,
                'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
                'rombel_id' => $rombel->id,
            ]);
        }

        return $user;
    }
}

test('guru menandai izin dengan bukti dan barisnya tersimpan manual', function () {
    Storage::fake('berkas');
    $rombel = rombelIzin();
    $siswa = siswaIzin('77', 'Siswa Izin Uji');
    $guru = guruKartuIzin($rombel);

    $this->actingAs($guru)->post(route('guru.absensi-qr.izin'), [
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'status' => 'izin',
        'berkas' => UploadedFile::fake()->image('surat.png'),
    ])->assertRedirect(route('guru.absensi-qr', ['rombel_id' => $rombel->id]))->assertSessionHas('success');

    $baris = Absensi::where(['siswa_id' => $siswa->id, 'rombel_id' => $rombel->id])->firstOrFail();

    expect($baris->status)->toBe('izin')
        ->and($baris->metode)->toBe('manual')
        ->and($baris->dicatat_oleh)->toBe($guru->id)
        ->and($baris->jam_datang)->toBeNull()
        ->and($baris->berkas_path)->not->toBeNull();

    Storage::disk('berkas')->assertExists($baris->berkas_path);
});

test('menandai sakit tanpa bukti tetap tercatat tanpa bukti', function () {
    Storage::fake('berkas');
    $rombel = rombelIzin();
    $siswa = siswaIzin('78', 'Siswa Sakit Uji');
    $guru = guruKartuIzin($rombel);

    $this->actingAs($guru)->post(route('guru.absensi-qr.izin'), [
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'status' => 'sakit',
    ])->assertRedirect()->assertSessionHas('success');

    $baris = Absensi::where(['siswa_id' => $siswa->id, 'rombel_id' => $rombel->id])->firstOrFail();

    expect($baris->status)->toBe('sakit')
        ->and($baris->berkas_path)->toBeNull();
});

test('menandai siswa yang sudah tercatat mengembalikan pesan dan tidak mengubah baris', function () {
    $rombel = rombelIzin();
    $siswa = siswaIzin('79', 'Siswa Sudah Hadir');
    $guru = guruKartuIzin($rombel);

    Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => today()->toDateString(),
        'status' => 'hadir',
        'metode' => 'qr',
    ]);

    $this->actingAs($guru)->post(route('guru.absensi-qr.izin'), [
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'status' => 'izin',
    ])->assertRedirect()->assertSessionHas('error');

    expect(Absensi::where(['siswa_id' => $siswa->id, 'rombel_id' => $rombel->id])->count())->toBe(1);
    expect(Absensi::where('siswa_id', $siswa->id)->first()->status)->toBe('hadir');
});

test('siswa di luar daftar rombel ditolak', function () {
    $rombel = rombelIzin();
    $siswaLain = siswaIzin('80', 'Siswa Kelas Lain', rombelIzinLain());
    $guru = guruKartuIzin($rombel);

    $this->actingAs($guru)->post(route('guru.absensi-qr.izin'), [
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswaLain->id,
        'status' => 'izin',
    ])->assertForbidden();

    expect(Absensi::where('siswa_id', $siswaLain->id)->count())->toBe(0);
});

test('rombel milik guru lain ditolak', function () {
    $rombelLain = rombelIzinLain();
    $siswa = siswaIzin('81', 'Siswa Rommel Lain', $rombelLain);
    $guru = guruKartuIzin(rombelIzin());

    $this->actingAs($guru)->post(route('guru.absensi-qr.izin'), [
        'rombel_id' => $rombelLain->id,
        'siswa_id' => $siswa->id,
        'status' => 'izin',
    ])->assertForbidden();

    expect(Absensi::where('siswa_id', $siswa->id)->count())->toBe(0);
});

test('berkas bukan gambar ditolak dan baris tidak terbentuk', function () {
    Storage::fake('berkas');
    $rombel = rombelIzin();
    $siswa = siswaIzin('82', 'Siswa Berkas Pdf');
    $guru = guruKartuIzin($rombel);

    $this->actingAs($guru)->post(route('guru.absensi-qr.izin'), [
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'status' => 'izin',
        'berkas' => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('berkas');

    expect(Absensi::count())->toBe(0);
});

test('status selain izin dan sakit ditolak', function () {
    $rombel = rombelIzin();
    $siswa = siswaIzin('83', 'Siswa Status Alpa');
    $guru = guruKartuIzin($rombel);

    $this->actingAs($guru)->post(route('guru.absensi-qr.izin'), [
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'status' => 'alpa',
    ])->assertSessionHasErrors('status');

    expect(Absensi::count())->toBe(0);
});

test('guru biasa tanpa izin grid tetap bisa menandai izin', function () {
    $rombel = rombelIzin();
    $siswa = siswaIzin('84', 'Siswa Guru Murni');
    $guru = guruKartuIzin($rombel);

    // Jaga asumsinya: role guru memang tidak memegang absensis.create.
    expect($guru->can('absensis.create'))->toBeFalse();

    $this->actingAs($guru)->post(route('guru.absensi-qr.izin'), [
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'status' => 'sakit',
    ])->assertRedirect()->assertSessionHas('success');

    expect(Absensi::where(['siswa_id' => $siswa->id, 'metode' => 'manual'])->count())->toBe(1);
});

test('guru bisa mengunggah bukti menyusul dan menggantinya', function () {
    Storage::fake('berkas');
    $rombel = rombelIzin();
    $siswa = siswaIzin('85', 'Siswa Bukti Menyusul');
    $guru = guruKartuIzin($rombel);

    $baris = Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => today()->toDateString(),
        'status' => 'izin',
        'metode' => 'manual',
        'dicatat_oleh' => $guru->id,
    ]);

    $this->actingAs($guru)
        ->post(route('guru.absensi-qr.izin.bukti', $baris), ['berkas' => UploadedFile::fake()->image('pertama.jpg')])
        ->assertRedirect()->assertSessionHas('success');

    $baris->refresh();
    $pertama = $baris->berkas_path;

    expect($pertama)->not->toBeNull();
    Storage::disk('berkas')->assertExists($pertama);

    $this->actingAs($guru)
        ->post(route('guru.absensi-qr.izin.bukti', $baris), ['berkas' => UploadedFile::fake()->image('kedua.jpg')])
        ->assertRedirect()->assertSessionHas('success');

    $baris->refresh();

    expect($baris->berkas_path)->not->toBe($pertama);
    Storage::disk('berkas')->assertMissing($pertama);
    Storage::disk('berkas')->assertExists($baris->berkas_path);
});

test('menandai yang kalah balapan tidak menimpa baris pesaing', function () {
    $rombel = rombelIzin();
    $siswa = siswaIzin('88', 'Siswa Balapan Izin');
    $guru = guruKartuIzin($rombel);

    // Simulasi balapan sungguhan: perangkat lain menulis baris kunci yang
    // sama tepat sebelum simpan kartu ini dijalankan (pola AbsensiInputTest).
    $balapan = false;

    Absensi::creating(function () use (&$balapan, $rombel, $siswa) {
        if ($balapan) {
            return;
        }

        $balapan = true;

        DB::table('absensis')->insert([
            'rombel_id' => $rombel->id,
            'siswa_id' => $siswa->id,
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'jam_datang' => '06:30:00',
            'metode' => 'qr',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    $this->actingAs($guru)->post(route('guru.absensi-qr.izin'), [
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'status' => 'izin',
    ])->assertRedirect()->assertSessionHas('error');

    $baris = Absensi::where('siswa_id', $siswa->id)->firstOrFail();

    expect(Absensi::where('siswa_id', $siswa->id)->count())->toBe(1)
        ->and($baris->status)->toBe('hadir')
        ->and($baris->metode)->toBe('qr')
        ->and(substr((string) $baris->jam_datang, 0, 5))->toBe('06:30');

    Absensi::flushEventListeners();
});

test('kartu izin tampil dengan daftar siswa rombel terpilih', function () {
    $rombel = rombelIzin();
    $siswa = siswaIzin('86', 'Siswa Kartu Uji');
    $guru = guruKartuIzin($rombel);

    $this->actingAs($guru)->get(route('guru.absensi-qr'))->assertOk()
        ->assertSee('Catatan Izin/Sakit')
        ->assertSee('Siswa Kartu Uji')
        ->assertSee((string) route('guru.absensi-qr.izin'));
});

test('rombel di luar cakupan tidak muncul sebagai pilihan kartu', function () {
    $rombelA = rombelIzin();
    $guru = guruKartuIzin($rombelA);

    $guruBaris = Guru::where('user_id', $guru->id)->firstOrFail();
    $rombelKeduaGuru = rombelIzinLain('7C');
    Pengampu::create([
        'guru_id' => $guruBaris->id,
        'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
        'rombel_id' => $rombelKeduaGuru->id,
    ]);
    rombelIzinLain('7D');

    $html = $this->actingAs($guru)->get(route('guru.absensi-qr'))->assertOk()->getContent();

    expect($html)->toContain('7A')->toContain('7C')->not->toContain('7D');
});

test('baris yang sudah tercatat tampil sebagai badge dan tanpa form', function () {
    $rombel = rombelIzin();
    $siswa = siswaIzin('87', 'Siswa Berbadge');
    $guru = guruKartuIzin($rombel);

    Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => today()->toDateString(),
        'status' => 'izin',
        'metode' => 'manual',
    ]);

    $html = $this->actingAs($guru)->get(route('guru.absensi-qr'))->assertOk()->getContent();

    expect($html)->toContain('<span class="badge-nexus badge-info">Izin</span>')
        ->not->toContain('name="status"');
});

test('guru tanpa penugasan melihat keadaan kosong kartu', function () {
    $guru = guruKartuIzin(null);

    $this->actingAs($guru)->get(route('guru.absensi-qr'))->assertOk()
        ->assertSee('Tidak ada rombel yang diampu');
});
