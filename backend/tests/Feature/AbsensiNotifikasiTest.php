<?php

use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\Absensi;
use App\Models\NotifikasiLog;
use App\Models\Pengaturan;
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
use Illuminate\Support\Facades\Http;

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

if (! function_exists('siswaNotifikasiUji')) {
    function siswaNotifikasiUji(string $nis, string $nama, ?string $noWa = '081233344455', ?Rombel $rombel = null): Siswa
    {
        static $n = 0;
        $n++;

        $rombel ??= Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();

        $user = User::factory()->create(['username' => 'siswa-notif-'.$n, 'name' => $nama]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'nisn' => sprintf('008040%04d', $n),
            'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
            'kelas_id' => $rombel->kelas_id,
            'is_aktif' => true,
            'nama_ayah' => 'Ayah '.$nama,
            'no_hp_orang_tua' => $noWa,
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

if (! function_exists('rombelNotifikasiUji')) {
    function rombelNotifikasiUji(): Rombel
    {
        return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();
    }
}

test('batch alpa mengantrekan notifikasi log-only', function () {
    $rombel = rombelNotifikasiUji();
    $siswa = siswaNotifikasiUji('7001', 'Anak Notif Satu');

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => now()->toDateString(),
        'status' => [$siswa->id => 'alpa'],
    ])->assertSessionHas('success');

    $log = NotifikasiLog::first();
    expect($log)->not->toBeNull()
        ->and($log->tujuan)->toBe('081233344455')
        ->and($log->pesan)->toContain('ALPA')->toContain('Anak Notif Satu')
        ->and($log->status)->toBe('antri')
        ->and($log->respons)->toContain('log-only');
});

test('batch tanpa alpa tidak membuat log', function () {
    $rombel = rombelNotifikasiUji();
    $siswa = siswaNotifikasiUji('7001', 'Anak Notif Satu');

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => now()->toDateString(),
        'status' => [$siswa->id => 'hadir'],
    ])->assertSessionHas('success');

    expect(NotifikasiLog::count())->toBe(0);
});

test('siswa tanpa no WA dilewati notifikasi', function () {
    $rombel = rombelNotifikasiUji();
    $siswa = siswaNotifikasiUji('7001', 'Anak Notif Satu', null);

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => now()->toDateString(),
        'status' => [$siswa->id => 'alpa'],
    ])->assertSessionHas('success');

    expect(NotifikasiLog::count())->toBe(0);
});

test('job menandai terkirim saat gateway menjawab ok', function () {
    Http::fake(fn () => Http::response('ok', 200));
    config()->set('services.whatsapp.url', 'https://wa.example.test/kirim');

    $log = NotifikasiLog::create(['tipe' => 'whatsapp', 'tujuan' => '0812', 'pesan' => 'Tes']);

    (new KirimNotifikasiWhatsapp($log->id))->handle();

    expect($log->fresh()->status)->toBe('terkirim');
});

test('job menandai gagal saat gateway error', function () {
    Http::fake(fn () => Http::response('sibuk', 500));
    config()->set('services.whatsapp.url', 'https://wa.example.test/kirim');

    $log = NotifikasiLog::create(['tipe' => 'whatsapp', 'tujuan' => '0812', 'pesan' => 'Tes']);

    try {
        (new KirimNotifikasiWhatsapp($log->id))->handle();
        $lempar = false;
    } catch (Throwable) {
        $lempar = true;
    }

    expect($lempar)->toBeTrue()->and($log->fresh()->status)->toBe('gagal');
});

test('command manual hanya mengingatkan yang belum absen', function () {
    $rombel = rombelNotifikasiUji();
    $hadir = siswaNotifikasiUji('7001', 'Anak Notif Satu');
    $belum = siswaNotifikasiUji('7002', 'Anak Notif Dua');
    $tanggal = '2026-09-01';

    Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $hadir->id,
        'tanggal' => $tanggal,
        'status' => 'hadir',
        'metode' => 'manual',
    ]);

    $this->artisan('absensi:cek-belum-hadir', ['--tanggal' => $tanggal])
        ->expectsOutputToContain('pengingat belum-hadir dibuat')
        ->assertSuccessful();

    expect(NotifikasiLog::count())->toBe(1)
        ->and(NotifikasiLog::first()->pesan)->toContain('Anak Notif Dua')->toContain('belum tercatat hadir');
});

test('command terjadwal self-gating sekali sehari', function () {
    $rombel = rombelNotifikasiUji();
    siswaNotifikasiUji('7001', 'Anak Notif Satu');
    Pengaturan::updateOrCreate(['kunci' => 'cek_belum_hadir_terakhir'], ['nilai' => now()->toDateString()]);

    $this->artisan('absensi:cek-belum-hadir')->assertSuccessful();

    expect(NotifikasiLog::count())->toBe(0);
});
