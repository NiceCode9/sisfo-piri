<?php

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\WaliKelas;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
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

if (! function_exists('buatPengampuDasar')) {
    function buatPengampuDasar(): array
    {
        $user = User::factory()->create(['username' => 'guru-pengampu']);
        $guru = Guru::create(['user_id' => $user->id, 'nama' => 'Guru Pengampu', 'jenis_kelamin' => 'L', 'is_aktif' => true]);
        $mapel = MataPelajaran::create(['kode' => 'MTK', 'nama' => 'Matematika', 'kelompok' => 'A', 'kkm' => 75]);
        $kelas = Kelas::create(['nama_kelas' => '7A', 'tingkat' => '7']);
        $tahun = TahunAjaran::aktif()->first();

        return compact('guru', 'mapel', 'kelas', 'tahun');
    }
}

test('tamu tidak dapat membuka daftar pengampu', function () {
    $this->get(route('admin.pengampus.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka pengampu', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.pengampus.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar pengampu', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.pengampus.index'));
    $response->assertOk()->assertSee('Daftar Penugasan');
});

test('super-admin dapat menambah penugasan', function () {
    $d = buatPengampuDasar();

    $response = $this->actingAs(superAdmin())->post(route('admin.pengampus.store'), [
        'guru_id' => $d['guru']->id,
        'mata_pelajaran_id' => $d['mapel']->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $d['tahun']->id,
    ]);

    $response->assertRedirect(route('admin.pengampus.index'));
    expect(Pengampu::count())->toBe(1);
});

test('duplikat mapel-kelas-tahun ditolak', function () {
    $d = buatPengampuDasar();
    Pengampu::create([
        'guru_id' => $d['guru']->id,
        'mata_pelajaran_id' => $d['mapel']->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $d['tahun']->id,
    ]);

    $user2 = User::factory()->create(['username' => 'guru-lain']);
    $guru2 = Guru::create(['user_id' => $user2->id, 'nama' => 'Guru Lain', 'jenis_kelamin' => 'P', 'is_aktif' => true]);

    $response = $this->actingAs(superAdmin())->post(route('admin.pengampus.store'), [
        'guru_id' => $guru2->id,
        'mata_pelajaran_id' => $d['mapel']->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $d['tahun']->id,
    ]);

    $response->assertSessionHasErrors('mata_pelajaran_id');
    expect(Pengampu::count())->toBe(1);
});

test('mapel sama boleh beda tahun (histori utuh)', function () {
    $d = buatPengampuDasar();
    Pengampu::create([
        'guru_id' => $d['guru']->id,
        'mata_pelajaran_id' => $d['mapel']->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $d['tahun']->id,
    ]);

    $tahunLalu = TahunAjaran::where('status_aktif', false)->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.pengampus.store'), [
        'guru_id' => $d['guru']->id,
        'mata_pelajaran_id' => $d['mapel']->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $tahunLalu->id,
    ]);

    $response->assertRedirect(route('admin.pengampus.index'));
    expect(Pengampu::count())->toBe(2);
});

test('admin tanpa permission delete tidak dapat menghapus penugasan', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $d = buatPengampuDasar();
    $pengampu = Pengampu::create([
        'guru_id' => $d['guru']->id,
        'mata_pelajaran_id' => $d['mapel']->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $d['tahun']->id,
    ]);

    $this->actingAs($admin)->delete(route('admin.pengampus.destroy', $pengampu))->assertForbidden();
    expect(Pengampu::find($pengampu->id))->not->toBeNull();
});

test('halaman salin dapat ditampilkan', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.pengampus.salin'));
    $response->assertOk()->assertSee('Salin Penugasan');
});

test('salin duplikasi penugasan dan wali ke tahun tujuan', function () {
    $d = buatPengampuDasar();
    $tahunBaru = TahunAjaran::create([
        'nama_tahun_ajaran' => '2027/2028',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'status_aktif' => false,
    ]);
    Pengampu::create([
        'guru_id' => $d['guru']->id,
        'mata_pelajaran_id' => $d['mapel']->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $d['tahun']->id,
    ]);
    WaliKelas::create([
        'guru_id' => $d['guru']->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $d['tahun']->id,
    ]);

    $response = $this->actingAs(superAdmin())->post(route('admin.pengampus.salin.proses'), [
        'tahun_sumber_id' => $d['tahun']->id,
        'tahun_tujuan_id' => $tahunBaru->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(Pengampu::where('tahun_ajaran_id', $tahunBaru->id)->count())->toBe(1)
        ->and(WaliKelas::where('tahun_ajaran_id', $tahunBaru->id)->count())->toBe(1);
});

test('salin idempoten, baris yang sudah ada dilewati', function () {
    $d = buatPengampuDasar();
    $tahunBaru = TahunAjaran::create([
        'nama_tahun_ajaran' => '2027/2028',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'status_aktif' => false,
    ]);
    Pengampu::create([
        'guru_id' => $d['guru']->id,
        'mata_pelajaran_id' => $d['mapel']->id,
        'kelas_id' => $d['kelas']->id,
        'tahun_ajaran_id' => $d['tahun']->id,
    ]);

    $data = ['tahun_sumber_id' => $d['tahun']->id, 'tahun_tujuan_id' => $tahunBaru->id];
    $this->actingAs(superAdmin())->post(route('admin.pengampus.salin.proses'), $data)->assertRedirect();
    $this->actingAs(superAdmin())->post(route('admin.pengampus.salin.proses'), $data)->assertRedirect();

    expect(Pengampu::where('tahun_ajaran_id', $tahunBaru->id)->count())->toBe(1);
});

test('salin menolak bila sumber sama dengan tujuan', function () {
    $d = buatPengampuDasar();

    $this->actingAs(superAdmin())->post(route('admin.pengampus.salin.proses'), [
        'tahun_sumber_id' => $d['tahun']->id,
        'tahun_tujuan_id' => $d['tahun']->id,
    ])->assertSessionHasErrors('tahun_sumber_id');
});
