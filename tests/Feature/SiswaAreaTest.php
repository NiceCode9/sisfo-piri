<?php

use App\Models\Absensi;
use App\Models\Kelas;
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
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
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

test('ortu di admin root ditolak', function () {
    $this->actingAs(buatOrtuArea())->get(route('admin.dashboard'))->assertForbidden();
});

test('siswa dapat membuka dashboard dan profil sendiri', function () {
    $d = buatAkunSiswaArea('Anak Area Satu', '5001');

    $this->actingAs($d['user'])->get(route('siswa.dashboard'))->assertOk();
    $this->actingAs($d['user'])->get(route('siswa.profil'))
        ->assertOk()->assertSee('Anak Area Satu')->assertSee('5001');
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

    $this->actingAs($a['user'])->get(route('siswa.kelas'))
        ->assertOk()->assertSee('7A')->assertDontSee('7B');
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
