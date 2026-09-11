<?php

use App\Models\Kelas;
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
use Database\Seeders\RombelSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
    $this->seed(RombelSeeder::class);
});

test('backfill membuat satu rombel per pasangan kelas tahun beserta wali', function () {
    $tahun = TahunAjaran::aktif()->first();

    $rombel7A = Rombel::where('kelas_id', Kelas::where('nama_kelas', '7A')->first()->id)
        ->where('tahun_ajaran_id', $tahun->id)
        ->first();

    expect($rombel7A)->not->toBeNull()
        ->and($rombel7A->waliGuru->nama)->toBe('Guru A');

    // 4 kelas × 1 tahun aktif dari seeder
    expect(Rombel::count())->toBe(4);
});

test('pengampu lama tertaut ke rombel yang cocok', function () {
    $pengampu = Pengampu::first();

    expect($pengampu->rombel_id)->not->toBeNull()
        ->and($pengampu->rombel->kelas_id)->toBe($pengampu->kelas_id)
        ->and($pengampu->rombel->tahun_ajaran_id)->toBe($pengampu->tahun_ajaran_id);
});

test('unique kelas tahun ditolak', function () {
    $rombel = Rombel::first();

    expect(fn () => Rombel::create([
        'kelas_id' => $rombel->kelas_id,
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
    ]))->toThrow(QueryException::class);
});

test('histori lengkap satu rombel memuat penugasan wali dan siswa', function () {
    $tahun = TahunAjaran::aktif()->first();
    $kelas = Kelas::where('nama_kelas', '7A')->first();

    $user = User::factory()->create(['username' => '0070099001']);
    $user->assignRole('siswa');
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nisn' => '0070099001',
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

    $rombel = Rombel::where('kelas_id', $kelas->id)->where('tahun_ajaran_id', $tahun->id)->firstOrFail();
    $histori = $rombel->historiLengkap();

    expect($histori['penugasan'])->not->toBeEmpty()
        ->and($histori['wali']->nama)->toBe('Guru A')
        ->and($histori['siswa']->pluck('id')->contains($siswa->id))->toBeTrue();
});
