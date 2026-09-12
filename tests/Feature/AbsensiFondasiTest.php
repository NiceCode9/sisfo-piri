<?php

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\WaliMurid;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

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

if (! function_exists('buatSiswaAbsensi')) {
    function buatSiswaAbsensi(array $overrides = []): Siswa
    {
        static $n = 0;
        $n++;

        return Siswa::create(array_merge([
            'nis' => sprintf('200%04d', $n),
            'nisn' => sprintf('008009%04d', $n),
            'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
            'kelas_id' => Kelas::where('nama_kelas', '7A')->firstOrFail()->id,
            'is_aktif' => true,
            'nama_ayah' => 'Ayah Absensi '.$n,
            'no_hp_orang_tua' => '081200000'.sprintf('%02d', $n),
        ], $overrides));
    }
}

if (! function_exists('buatRombelAbsensi')) {
    function buatRombelAbsensi(): Rombel
    {
        return Rombel::firstOrCreate([
            'kelas_id' => Kelas::where('nama_kelas', '7A')->firstOrFail()->id,
            'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        ]);
    }
}

test('siswa baru otomatis mendapat akun orang-tua', function () {
    $siswa = buatSiswaAbsensi();

    $ortu = User::where('username', 'ortu-'.$siswa->nisn)->first();
    expect($ortu)->not->toBeNull()
        ->and($ortu->hasRole('orang-tua'))->toBeTrue();

    $tautan = WaliMurid::where('siswa_id', $siswa->id)->first();
    expect($tautan)->not->toBeNull()
        ->and($tautan->user_id)->toBe($ortu->id)
        ->and($tautan->no_whatsapp)->toBe($siswa->no_hp_orang_tua);
});

test('kakak-beradik dengan no WA sama memakai satu akun ortu', function () {
    $s1 = buatSiswaAbsensi(['no_hp_orang_tua' => '081299999999']);
    $s2 = buatSiswaAbsensi(['no_hp_orang_tua' => '081299999999']);

    expect(WaliMurid::where('siswa_id', $s1->id)->first()->user_id)
        ->toBe(WaliMurid::where('siswa_id', $s2->id)->first()->user_id)
        ->and(User::where('username', 'like', 'ortu-%')->count())->toBe(1);
});

test('backfill membuat akun ortu yang belum ada', function () {
    $siswa = buatSiswaAbsensi();
    $siswa->waliMurids()->delete();
    User::where('username', 'ortu-'.$siswa->nisn)->delete();

    $this->artisan('siswa:generate-orangtua')
        ->expectsOutputToContain('akun orang-tua dibuat')
        ->assertSuccessful();

    expect(WaliMurid::where('siswa_id', $siswa->id)->exists())->toBeTrue();
});

test('duplikat absensi siswa tanggal sama ditolak', function () {
    $rombel = buatRombelAbsensi();
    $siswa = buatSiswaAbsensi();
    $admin = superAdmin();

    Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => '2026-09-12',
        'status' => 'hadir',
        'metode' => 'manual',
        'dicatat_oleh' => $admin->id,
    ]);

    expect(fn () => Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => '2026-09-12',
        'status' => 'sakit',
        'metode' => 'manual',
        'dicatat_oleh' => $admin->id,
    ]))->toThrow(QueryException::class);
});

test('qr token unik antar siswa', function () {
    $s1 = buatSiswaAbsensi();
    $s1->update(['qr_token' => 'token-unik-123']);
    $s2 = buatSiswaAbsensi();

    expect(fn () => $s2->update(['qr_token' => 'token-unik-123']))->toThrow(QueryException::class);
});

test('role guru-piket dan orang-tua memiliki permission absensi', function () {
    expect(Role::findByName('guru-piket')->hasPermissionTo('absensis.create'))->toBeTrue()
        ->and(Role::findByName('guru-piket')->hasPermissionTo('absensis.delete'))->toBeFalse()
        ->and(Role::findByName('orang-tua')->hasPermissionTo('absensis.view'))->toBeTrue()
        ->and(Role::findByName('orang-tua')->hasPermissionTo('absensis.create'))->toBeFalse();
});

test('pengaturan batas terlambat default 07:00', function () {
    $this->seed(PengaturanSeeder::class);

    expect(Pengaturan::nilai('batas_terlambat'))->toBe('07:00');
});
