<?php

use App\Models\Absensi;
use App\Models\BerkasCalonSiswa;
use App\Models\CalonSiswa;
use App\Models\JalurPendaftaran;
use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(MenuSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
});

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

if (! function_exists('buatAkunSiswaArea')) {
    function buatAkunSiswaArea(string $nama, string $nis, ?string $kelasNama = '7A'): array
    {
        static $n = 0;
        $n++;

        $user = User::factory()->create(['username' => 'siswa-area-'.$n, 'name' => $nama]);
        $user->assignRole('siswa');

        $kelas = Kelas::where('nama_kelas', $kelasNama)->firstOrFail();
        $tahun = TahunAjaran::aktif()->first();

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'nisn' => sprintf('008020%04d', $n),
            'tahun_ajaran_id' => $tahun->id,
            'kelas_id' => $kelas->id,
            'is_aktif' => true,
        ]);

        RiwayatKelas::create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelas->id,
            'tahun_ajaran_id' => $tahun->id,
            'status' => 'aktif',
        ]);

        return compact('user', 'siswa');
    }
}

if (! function_exists('buatOrtuArea')) {
    function buatOrtuArea(): User
    {
        $user = User::factory()->create(['username' => 'ortu-area']);
        $user->assignRole('orang-tua');

        return $user;
    }
}

test('siswa tidak dapat membuka daftar siswa admin', function () {
    $d = buatAkunSiswaArea('Anak Area Satu', '5001');

    $this->actingAs($d['user'])->get(route('admin.siswas.index'))->assertForbidden();
});

test('ortu tidak dapat membuka grid absensi admin', function () {
    $this->actingAs(buatOrtuArea())->get(route('admin.absensis.index'))->assertForbidden();
});

test('siswa di admin root diarahkan ke dashboard siswa', function () {
    $d = buatAkunSiswaArea('Anak Area Satu', '5001');

    $this->actingAs($d['user'])->get(route('admin.dashboard'))->assertRedirect(route('siswa.dashboard'));
});

test('ortu di admin root diarahkan ke dashboard ortu', function () {
    $this->actingAs(buatOrtuArea())->get(route('admin.dashboard'))->assertRedirect(route('ortu.dashboard'));
});

test('siswa dapat membuka dashboard dan profil sendiri', function () {
    $d = buatAkunSiswaArea('Anak Area Satu', '5001');

    $this->actingAs($d['user'])->get(route('siswa.dashboard'))->assertOk();
    $this->actingAs($d['user'])->get(route('siswa.profil'))
        ->assertOk()->assertSee('Anak Area Satu')->assertSee('5001');
});

test('siswa tanpa menu tidak melihat header kategori sidebar', function () {
    $d = buatAkunSiswaArea('Anak Area Satu', '5001');

    $this->actingAs($d['user'])->get(route('siswa.dashboard'))
        ->assertOk()->assertDontSee('sidebar-label', false);
});

test('superadmin tetap melihat header kategori sidebar', function () {
    $this->actingAs(superAdmin())->get(route('admin.dashboard'))
        ->assertOk()->assertSee('PPDB')->assertSee('Akademik')->assertSee('Sistem');
});

test('siswa tidak melihat data siswa lain', function () {
    $a = buatAkunSiswaArea('Anak Area Satu', '5001');
    buatAkunSiswaArea('Anak Area Dua', '5002');

    $this->actingAs($a['user'])->get(route('siswa.profil'))
        ->assertOk()->assertSee('Anak Area Satu')->assertDontSee('Anak Area Dua');
});

test('siswa dapat ubah password kontak dan foto', function () {
    Storage::fake('public');
    $d = buatAkunSiswaArea('Anak Area Satu', '5001');

    $response = $this->actingAs($d['user'])->put(route('siswa.profil.update'), [
        'password_saat_ini' => 'password',
        'password' => 'rahasia-baru',
        'password_confirmation' => 'rahasia-baru',
        'nama_ayah' => 'Ayah Baru',
        'no_hp_orang_tua' => '081299988877',
        'foto' => UploadedFile::fake()->image('foto.jpg'),
    ]);

    $response->assertRedirect(route('siswa.profil'));
    expect($d['siswa']->fresh()->nama_ayah)->toBe('Ayah Baru')
        ->and($d['siswa']->fresh()->foto_path)->not->toBeNull();
    Storage::disk('public')->assertExists($d['siswa']->fresh()->foto_path);
});

test('siswa tidak dapat mengubah nis', function () {
    $d = buatAkunSiswaArea('Anak Area Satu', '5001');

    $this->actingAs($d['user'])->put(route('siswa.profil.update'), [
        'nis' => '9999',
    ])->assertRedirect(route('siswa.profil'));

    expect($d['siswa']->fresh()->nis)->toBe('5001');
});

test('riwayat kelas hanya milik sendiri', function () {
    $a = buatAkunSiswaArea('Anak Area Satu', '5001');
    buatAkunSiswaArea('Anak Area Dua', '5002', '7B');

    $respons = $this->actingAs($a['user'])->get(route('siswa.kelas'));
    $respons->assertOk();

    expect(htmlTanpaToken($respons->getContent()))
        ->toContain('7A')
        ->not->toContain('7B');
});

test('absensi dan rekap hanya milik sendiri', function () {
    $a = buatAkunSiswaArea('Anak Area Satu', '5001');
    $b = buatAkunSiswaArea('Anak Area Dua', '5002');
    $rombel = Rombel::where('kelas_id', $a['siswa']->kelas_id)
        ->where('tahun_ajaran_id', $a['siswa']->tahun_ajaran_id)
        ->firstOrFail();
    $hari = now()->toDateString();

    Absensi::create(['rombel_id' => $rombel->id, 'siswa_id' => $a['siswa']->id, 'tanggal' => $hari, 'status' => 'hadir', 'metode' => 'qr', 'jam_datang' => '06:50:00']);
    Absensi::create(['rombel_id' => $rombel->id, 'siswa_id' => $b['siswa']->id, 'tanggal' => $hari, 'status' => 'alpa', 'metode' => 'manual']);

    $this->actingAs($a['user'])->get(route('siswa.absensi', ['bulan' => substr($hari, 0, 7)]))
        ->assertOk()->assertSee('Hadir: 1')->assertDontSee('Alpa: 1');
});

/**
 * Calon milik siswa uji, dengan berkas yang perlu diperbaiki admin.
 *
 * @return array{0: User, 1: CalonSiswa, 2: BerkasCalonSiswa, 3: string}
 */
function buatCalonPerluPerbaikan(array $perlu = ['ijazah_path']): array
{
    static $n = 0;
    $n++;

    $user = User::factory()->create(['username' => 'calon-area-'.$n]);
    $user->assignRole('siswa');

    $calon = CalonSiswa::create([
        'user_id' => $user->id,
        'jalur_pendaftaran_id' => JalurPendaftaran::firstOrFail()->id,
        'tahun_ajaran_id' => TahunAjaran::aktif()->firstOrFail()->id,
        'no_pendaftaran' => sprintf('PPDB-2026-%04d', 900 + $n),
        'nik' => sprintf('880000000000%04d', $n),
        'nisn' => sprintf('880000%04d', $n),
        'nama_lengkap' => 'Calon Perlu Perbaikan '.$n,
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Sleman',
        'tanggal_lahir' => '2010-05-10',
        'agama' => 'Islam',
        'alamat' => 'Jl Perbaikan '.$n,
        'status_pendaftaran' => 'menunggu',
    ]);

    $lama = UploadedFile::fake()->create('lama.pdf', 10, 'application/pdf')->store('berkas', 'berkas');

    $berkas = $calon->berkasCalonSiswa()->create([
        'ijazah_path' => $lama,
        'status_verifikasi' => false,
        'berkas_perlu_perbaikan' => $perlu,
        'alasan_penolakan' => 'Scan buram',
        'catatan_berkas' => 'Mohon unggah ulang',
    ]);

    return [$user, $calon, $berkas, $lama];
}

test('siswa bisa mengunggah ulang berkas yang diminta diperbaiki', function () {
    Storage::fake('berkas');
    [$user, $calon, $berkas, $lama] = buatCalonPerluPerbaikan(['ijazah_path', 'kk_path']);

    $this->actingAs($user)->put(route('siswa.berkas.update'), [
        'ijazah_path' => UploadedFile::fake()->create('baru.pdf', 20, 'application/pdf'),
    ])->assertSessionHas('success');

    $fresh = $berkas->fresh();

    // Field yang sudah diganti keluar dari daftar perlu perbaikan.
    expect($fresh->berkas_perlu_perbaikan)->toBe(['kk_path'])
        // Verifikasi dikembalikan ke admin karena isinya berubah.
        ->and($fresh->status_verifikasi)->toBeFalse()
        ->and($fresh->ijazah_path)->not->toBe($lama);

    Storage::disk('berkas')->assertExists($fresh->ijazah_path);
    // File lama dihapus SETELAH commit.
    Storage::disk('berkas')->assertMissing($lama);

    expect($fresh->catatan_berkas)->toContain('mengunggah ulang berkas');
});

test('siswa tidak boleh mengunggah berkas yang tidak diminta', function () {
    Storage::fake('berkas');
    [$user, $calon, $berkas] = buatCalonPerluPerbaikan(['ijazah_path']);
    $aktaLama = $berkas->akta_path;

    $this->actingAs($user)->put(route('siswa.berkas.update'), [
        'akta_path' => UploadedFile::fake()->create('akta.pdf', 20, 'application/pdf'),
    ])->assertSessionHas('error');

    expect($berkas->fresh()->akta_path)->toBe($aktaLama);
});

test('siswa lain tidak bisa mengunggah berkas milik calon yang bukan dirinya', function () {
    Storage::fake('berkas');
    [$user, $calon] = buatCalonPerluPerbaikan(['ijazah_path']);

    $orangLain = buatAkunSiswaArea('Bukan Pemilik', '5099');

    $this->actingAs($orangLain['user'])->put(route('siswa.berkas.update'), [
        'ijazah_path' => UploadedFile::fake()->create('jahat.pdf', 20, 'application/pdf'),
    ])->assertSessionHas('error');

    expect($calon->berkasCalonSiswa->fresh()->ijazah_path)->not->toBeNull();
});

test('siswa tidak bisa mengunggah berkas yang tidak sedang perlu perbaikan', function () {
    Storage::fake('berkas');
    [$user, $calon, $berkas, $lama] = buatCalonPerluPerbaikan([]);
    $berkas->update(['berkas_perlu_perbaikan' => []]);

    $this->actingAs($user)->put(route('siswa.berkas.update'), [
        'ijazah_path' => UploadedFile::fake()->create('baru.pdf', 20, 'application/pdf'),
    ])->assertSessionHas('error');

    expect($berkas->fresh()->ijazah_path)->toBe($lama);
});

test('form upload ulang hanya tampil saat ada berkas perlu perbaikan', function () {
    Storage::fake('berkas');
    [$user, , $berkas] = buatCalonPerluPerbaikan(['ijazah_path']);

    $this->actingAs($user)->get(route('siswa.dashboard'))
        ->assertOk()
        ->assertSee('Unggah Ulang Berkas')
        ->assertSee('Kirim Berkas');

    // Setelah semua diperbaiki, form hilang.
    $berkas->update(['berkas_perlu_perbaikan' => []]);

    $this->actingAs($user)->get(route('siswa.dashboard'))
        ->assertOk()
        ->assertDontSee('Unggah Ulang Berkas');
});

test('tamu dan orang tua tidak bisa mengunggah berkas', function () {
    Storage::fake('berkas');
    [$user, , $berkas, $lama] = buatCalonPerluPerbaikan(['ijazah_path']);

    $this->put(route('siswa.berkas.update'), ['ijazah_path' => UploadedFile::fake()->create('x.pdf', 20, 'application/pdf')])
        ->assertRedirect(route('login'));

    $this->actingAs(buatOrtuArea())
        ->put(route('siswa.berkas.update'), ['ijazah_path' => UploadedFile::fake()->create('x.pdf', 20, 'application/pdf')])
        ->assertForbidden();

    expect($berkas->fresh()->ijazah_path)->toBe($lama);
});

test('siswa tidak bisa menulis kolom berkas di luar daftar putih', function () {
    Storage::fake('berkas');
    [$user, $calon] = buatCalonPerluPerbaikan(['ijazah_path']);

    // `catatan_berkas` bukan kolom berkas: tidak divalidasi, dan karena
    // controller hanya membaca field daftar putih, nilainya tidak ikut tersimpan.
    $this->actingAs($user)->put(route('siswa.berkas.update'), [
        'ijazah_path' => UploadedFile::fake()->create('baru.pdf', 20, 'application/pdf'),
        'catatan_berkas' => 'dibuat sendiri',
    ])->assertSessionHas('success');

    expect($calon->berkasCalonSiswa->fresh()->catatan_berkas)
        ->toContain('Mohon unggah ulang')
        ->not->toContain('dibuat sendiri');
});

test('permintaan tanpa berkas baru ditolak', function () {
    Storage::fake('berkas');
    [$user, , $berkas, $lama] = buatCalonPerluPerbaikan(['ijazah_path']);

    $this->actingAs($user)->put(route('siswa.berkas.update'), [])
        ->assertSessionHas('error');

    expect($berkas->fresh()->ijazah_path)->toBe($lama);
});
