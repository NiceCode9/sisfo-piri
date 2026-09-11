<?php

use App\Models\Guru;
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
        ->and($pengampu->rombel)->not->toBeNull();
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

test('tamu tidak dapat membuka daftar rombel', function () {
    $this->get(route('admin.rombels.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka rombel', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.rombels.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar rombel', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.rombels.index'));
    $response->assertOk()->assertSee('Daftar Rombel');
});

test('super-admin dapat membentuk rombel beserta wali', function () {
    $tahun = TahunAjaran::aktif()->first();
    $kelas = Kelas::create(['nama_kelas' => '7Z', 'tingkat' => '7']);
    $guru = Guru::first();

    $response = $this->actingAs(superAdmin())->post(route('admin.rombels.store'), [
        'kelas_id' => $kelas->id,
        'tahun_ajaran_id' => $tahun->id,
        'wali_guru_id' => $guru->id,
    ]);

    $response->assertRedirect(route('admin.rombels.index'));
    $rombel = Rombel::where('kelas_id', $kelas->id)->where('tahun_ajaran_id', $tahun->id)->first();
    expect($rombel)->not->toBeNull()->and($rombel->wali_guru_id)->toBe($guru->id);
});

test('duplikat kelas-tahun ditolak saat tambah rombel', function () {
    $rombel = Rombel::first();

    $response = $this->actingAs(superAdmin())->post(route('admin.rombels.store'), [
        'kelas_id' => $rombel->kelas_id,
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
    ]);

    $response->assertSessionHasErrors('kelas_id');
});

test('super-admin dapat menghapus rombel tanpa penugasan', function () {
    $tahun = TahunAjaran::aktif()->first();
    $kelas = Kelas::create(['nama_kelas' => '7Z', 'tingkat' => '7']);
    $rombel = Rombel::create(['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun->id]);

    $this->actingAs(superAdmin())->delete(route('admin.rombels.destroy', $rombel))
        ->assertRedirect(route('admin.rombels.index'));
    expect(Rombel::find($rombel->id))->toBeNull();
});

test('halaman salin rombel dapat ditampilkan', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.rombels.salin'));
    $response->assertOk()->assertSee('Salin Rombel');
});

test('salin membentuk rombel dan penugasan di tahun tujuan', function () {
    $tahun = TahunAjaran::aktif()->first();
    $tahunBaru = TahunAjaran::create([
        'nama_tahun_ajaran' => '2027/2028',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'status_aktif' => false,
    ]);

    $response = $this->actingAs(superAdmin())->post(route('admin.rombels.salin.proses'), [
        'tahun_sumber_id' => $tahun->id,
        'tahun_tujuan_id' => $tahunBaru->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(Rombel::where('tahun_ajaran_id', $tahunBaru->id)->count())->toBe(4)
        ->and(Pengampu::whereHas('rombel', fn ($q) => $q->where('tahun_ajaran_id', $tahunBaru->id))->count())->toBe(4);
});

test('salin idempoten dan menolak sumber sama dengan tujuan', function () {
    $tahun = TahunAjaran::aktif()->first();
    $tahunBaru = TahunAjaran::create([
        'nama_tahun_ajaran' => '2027/2028',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'status_aktif' => false,
    ]);

    $data = ['tahun_sumber_id' => $tahun->id, 'tahun_tujuan_id' => $tahunBaru->id];
    $this->actingAs(superAdmin())->post(route('admin.rombels.salin.proses'), $data)->assertRedirect();
    $this->actingAs(superAdmin())->post(route('admin.rombels.salin.proses'), $data)->assertRedirect();

    expect(Rombel::where('tahun_ajaran_id', $tahunBaru->id)->count())->toBe(4);

    $this->actingAs(superAdmin())->post(route('admin.rombels.salin.proses'), [
        'tahun_sumber_id' => $tahun->id,
        'tahun_tujuan_id' => $tahun->id,
    ])->assertSessionHasErrors('tahun_sumber_id');
});

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}
