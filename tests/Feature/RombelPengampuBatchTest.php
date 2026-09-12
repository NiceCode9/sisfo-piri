<?php

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\Rombel;
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

if (! function_exists('buatMapelBatch')) {
    function buatMapelBatch(string $kode, string $nama): MataPelajaran
    {
        return MataPelajaran::create([
            'kode' => $kode,
            'nama' => $nama,
            'kelompok' => 'A',
            'kkm' => 75,
            'is_aktif' => true,
        ]);
    }
}

if (! function_exists('buatRombelBatch')) {
    function buatRombelBatch(): array
    {
        $user = User::factory()->create(['username' => 'guru-batch']);
        $guru = Guru::create(['user_id' => $user->id, 'nama' => 'Guru Batch', 'jenis_kelamin' => 'L', 'is_aktif' => true]);
        $mapel = buatMapelBatch('MTK', 'Matematika');
        $kelas = Kelas::create(['nama_kelas' => '7A', 'tingkat' => '7']);
        $tahun = TahunAjaran::aktif()->first();
        $rombel = Rombel::create(['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun->id]);

        return compact('guru', 'mapel', 'kelas', 'tahun', 'rombel');
    }
}

test('batch tiga baris tersimpan sekaligus dari show rombel', function () {
    $d = buatRombelBatch();
    $ipa = buatMapelBatch('IPA', 'Ilmu Pengetahuan Alam');
    $bin = buatMapelBatch('BIN', 'Bahasa Indonesia');

    $response = $this->actingAs(superAdmin())->post(route('admin.rombels.pengampus.batch', $d['rombel']), [
        'baris' => [
            ['guru_id' => $d['guru']->id, 'mata_pelajaran_id' => $d['mapel']->id],
            ['guru_id' => $d['guru']->id, 'mata_pelajaran_id' => $ipa->id],
            ['guru_id' => $d['guru']->id, 'mata_pelajaran_id' => $bin->id],
        ],
    ]);

    $response->assertRedirect(route('admin.rombels.show', $d['rombel']));
    expect(Pengampu::where('rombel_id', $d['rombel']->id)->count())->toBe(3);
});

test('mapel duplikat dalam batch ditolak dan modal dibuka kembali', function () {
    $d = buatRombelBatch();

    $response = $this->actingAs(superAdmin())->post(route('admin.rombels.pengampus.batch', $d['rombel']), [
        'baris' => [
            ['guru_id' => $d['guru']->id, 'mata_pelajaran_id' => $d['mapel']->id],
            ['guru_id' => $d['guru']->id, 'mata_pelajaran_id' => $d['mapel']->id],
        ],
    ]);

    $response->assertSessionHasErrors('baris');
    expect(session('bukaModalPengampu'))->toBeTrue()
        ->and(Pengampu::count())->toBe(0);
});

test('duplikat terhadap penugasan existing ditolak atomik', function () {
    $d = buatRombelBatch();
    $ipa = buatMapelBatch('IPA', 'Ilmu Pengetahuan Alam');
    Pengampu::create([
        'guru_id' => $d['guru']->id,
        'mata_pelajaran_id' => $d['mapel']->id,
        'rombel_id' => $d['rombel']->id,
    ]);

    $response = $this->actingAs(superAdmin())->post(route('admin.rombels.pengampus.batch', $d['rombel']), [
        'baris' => [
            ['guru_id' => $d['guru']->id, 'mata_pelajaran_id' => $d['mapel']->id],
            ['guru_id' => $d['guru']->id, 'mata_pelajaran_id' => $ipa->id],
        ],
    ]);

    $response->assertSessionHasErrors('baris.0.mata_pelajaran_id');
    expect(Pengampu::count())->toBe(1);
});

test('user tanpa permission create ditolak batch', function () {
    $d = buatRombelBatch();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.rombels.pengampus.batch', $d['rombel']), [
        'baris' => [['guru_id' => $d['guru']->id, 'mata_pelajaran_id' => $d['mapel']->id]],
    ])->assertForbidden();

    expect(Pengampu::count())->toBe(0);
});

test('hapus penugasan dari show kembali ke show', function () {
    $d = buatRombelBatch();
    $pengampu = Pengampu::create([
        'guru_id' => $d['guru']->id,
        'mata_pelajaran_id' => $d['mapel']->id,
        'rombel_id' => $d['rombel']->id,
    ]);

    $this->from(route('admin.rombels.show', $d['rombel']))
        ->actingAs(superAdmin())
        ->delete(route('admin.pengampus.destroy', $pengampu))
        ->assertRedirect(route('admin.rombels.show', $d['rombel']));

    expect(Pengampu::find($pengampu->id))->toBeNull();
});

test('show rombel me-render modal batch dan opsi mapel aktif', function () {
    $d = buatRombelBatch();
    buatMapelBatch('IPA', 'Ilmu Pengetahuan Alam');

    $this->actingAs(superAdmin())->get(route('admin.rombels.show', $d['rombel']))
        ->assertOk()
        ->assertSee('modalTambahPengampu', false)
        ->assertSee('Tambah Baris')
        ->assertSee('IPA — Ilmu Pengetahuan Alam');
});
