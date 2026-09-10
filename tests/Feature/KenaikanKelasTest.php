<?php

use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
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

if (! function_exists('buatSiswaAktif')) {
    function buatSiswaAktif(array $overrides = []): Siswa
    {
        static $n = 0;
        $n++;

        $tahun = TahunAjaran::aktif()->first();
        $kelas = Kelas::firstOrCreate(['nama_kelas' => '7A'], ['tingkat' => '7']);

        return Siswa::create(array_merge([
            'nis' => sprintf('100%04d', $n),
            'tahun_ajaran_id' => $tahun->id,
            'kelas_id' => $kelas->id,
            'is_aktif' => true,
        ], $overrides));
    }
}

test('tamu tidak dapat membuka kenaikan kelas', function () {
    $this->get(route('admin.kenaikan.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka kenaikan', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.kenaikan.index'))->assertForbidden();
});

test('super-admin dapat membuka halaman kenaikan', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.kenaikan.index'));
    $response->assertOk()->assertSee('Kenaikan Kelas');
});

test('naikkan menulis riwayat dan pindah kelas', function () {
    $siswa = buatSiswaAktif();
    $kelas8 = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);
    $tahun = TahunAjaran::aktif()->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.kenaikan.naikkan'), [
        'siswa_ids' => [$siswa->id],
        'kelas_tujuan_id' => $kelas8->id,
        'tahun_tujuan_id' => $tahun->id,
    ]);

    $response->assertSessionHas('success');
    expect(RiwayatKelas::where('siswa_id', $siswa->id)->count())->toBe(1)
        ->and($siswa->fresh()->kelas_id)->toBe($kelas8->id);

    $riwayat = RiwayatKelas::where('siswa_id', $siswa->id)->first();
    expect($riwayat->status)->toBe('aktif')
        ->and($riwayat->tahun_ajaran_id)->toBe($tahun->id);
});

test('naikkan ganda tahun sama dilewati', function () {
    $siswa = buatSiswaAktif();
    $kelas8 = Kelas::create(['nama_kelas' => '8A', 'tingkat' => '8']);
    $tahun = TahunAjaran::aktif()->first();

    $payload = [
        'siswa_ids' => [$siswa->id],
        'kelas_tujuan_id' => $kelas8->id,
        'tahun_tujuan_id' => $tahun->id,
    ];

    $this->actingAs(superAdmin())->post(route('admin.kenaikan.naikkan'), $payload)->assertSessionHas('success');
    $this->actingAs(superAdmin())->post(route('admin.kenaikan.naikkan'), $payload)->assertSessionHas('success');

    expect(RiwayatKelas::where('siswa_id', $siswa->id)->count())->toBe(1);
});

test('luluskan menulis riwayat lulus dan nonaktifkan', function () {
    $siswa = buatSiswaAktif();
    $tahun = TahunAjaran::aktif()->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.kenaikan.luluskan'), [
        'siswa_ids' => [$siswa->id],
        'tahun_tujuan_id' => $tahun->id,
    ]);

    $response->assertSessionHas('success');

    $riwayat = RiwayatKelas::where('siswa_id', $siswa->id)->first();
    expect($riwayat)->not->toBeNull()
        ->and($riwayat->status)->toBe('lulus');
    expect($siswa->fresh()->is_aktif)->toBeFalse();
});

test('user tanpa permission execute ditolak memproses', function () {
    $user = User::factory()->create();
    $siswa = buatSiswaAktif();
    $tahun = TahunAjaran::aktif()->first();

    $this->actingAs($user)->post(route('admin.kenaikan.naikkan'), [
        'siswa_ids' => [$siswa->id],
        'tahun_tujuan_id' => $tahun->id,
    ])->assertForbidden();
});
