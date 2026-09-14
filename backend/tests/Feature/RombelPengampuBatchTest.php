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

if (! function_exists('buatGuruBatch')) {
    function buatGuruBatch(string $username, string $nama): Guru
    {
        $user = User::factory()->create(['username' => $username]);

        return Guru::create(['user_id' => $user->id, 'nama' => $nama, 'jenis_kelamin' => 'L', 'is_aktif' => true]);
    }
}

if (! function_exists('buatRombelBatch')) {
    function buatRombelBatch(): array
    {
        $guru = buatGuruBatch('guru-batch', 'Guru Batch');
        $mapel = buatMapelBatch('MTK', 'Matematika');
        $kelas = Kelas::create(['nama_kelas' => '7A', 'tingkat' => '7']);
        $tahun = TahunAjaran::aktif()->first();
        $rombel = Rombel::create(['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun->id]);

        return compact('guru', 'mapel', 'kelas', 'tahun', 'rombel');
    }
}

test('editor menyimpan beberapa mapel sekaligus', function () {
    $d = buatRombelBatch();
    $ipa = buatMapelBatch('IPA', 'Ilmu Pengetahuan Alam');
    $bin = buatMapelBatch('BIN', 'Bahasa Indonesia');

    $response = $this->actingAs(superAdmin())->post(route('admin.rombels.pengampus.batch', $d['rombel']), [
        'guru' => [
            $d['mapel']->id => $d['guru']->id,
            $ipa->id => $d['guru']->id,
            $bin->id => '',
        ],
    ]);

    $response->assertRedirect(route('admin.rombels.show', $d['rombel']));
    expect(Pengampu::where('rombel_id', $d['rombel']->id)->count())->toBe(2)
        ->and(Pengampu::where('rombel_id', $d['rombel']->id)->where('mata_pelajaran_id', $bin->id)->exists())->toBeFalse();
});

test('editor memperbarui guru mapel yang sudah terisi', function () {
    $d = buatRombelBatch();
    $baru = buatGuruBatch('guru-baru', 'Guru Baru');
    Pengampu::create([
        'guru_id' => $d['guru']->id,
        'mata_pelajaran_id' => $d['mapel']->id,
        'rombel_id' => $d['rombel']->id,
    ]);

    $this->actingAs(superAdmin())->post(route('admin.rombels.pengampus.batch', $d['rombel']), [
        'guru' => [$d['mapel']->id => $baru->id],
    ])->assertRedirect(route('admin.rombels.show', $d['rombel']));

    expect(Pengampu::where('rombel_id', $d['rombel']->id)->count())->toBe(1)
        ->and(Pengampu::first()->guru_id)->toBe($baru->id);
});

test('baris kosong diabaikan dan penugasan existing utuh', function () {
    $d = buatRombelBatch();
    $pengampu = Pengampu::create([
        'guru_id' => $d['guru']->id,
        'mata_pelajaran_id' => $d['mapel']->id,
        'rombel_id' => $d['rombel']->id,
    ]);

    $this->actingAs(superAdmin())->post(route('admin.rombels.pengampus.batch', $d['rombel']), [
        'guru' => [$d['mapel']->id => ''],
    ])->assertRedirect(route('admin.rombels.show', $d['rombel']));

    expect(Pengampu::find($pengampu->id))->not->toBeNull()
        ->and(Pengampu::find($pengampu->id)->guru_id)->toBe($d['guru']->id);
});

test('guru tidak dikenal ditolak', function () {
    $d = buatRombelBatch();

    $response = $this->actingAs(superAdmin())->post(route('admin.rombels.pengampus.batch', $d['rombel']), [
        'guru' => [$d['mapel']->id => 999999],
    ]);

    $response->assertSessionHasErrors('guru.'.$d['mapel']->id);
    expect(Pengampu::count())->toBe(0);
});

test('mapel tidak dikenal ditolak', function () {
    $d = buatRombelBatch();

    $response = $this->actingAs(superAdmin())->post(route('admin.rombels.pengampus.batch', $d['rombel']), [
        'guru' => [999999 => $d['guru']->id],
    ]);

    $response->assertSessionHasErrors('guru');
    expect(Pengampu::count())->toBe(0);
});

test('user tanpa permission create ditolak editor', function () {
    $d = buatRombelBatch();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.rombels.pengampus.batch', $d['rombel']), [
        'guru' => [$d['mapel']->id => $d['guru']->id],
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

test('show me-render satu select per mapel dengan prefill guru', function () {
    $d = buatRombelBatch();
    buatMapelBatch('IPA', 'Ilmu Pengetahuan Alam');
    Pengampu::create([
        'guru_id' => $d['guru']->id,
        'mata_pelajaran_id' => $d['mapel']->id,
        'rombel_id' => $d['rombel']->id,
    ]);

    $this->actingAs(superAdmin())->get(route('admin.rombels.show', $d['rombel']))
        ->assertOk()
        ->assertSee('form-penugasan', false)
        ->assertSee('name="guru['.$d['mapel']->id.']"', false)
        ->assertSee('name="guru[', false)
        ->assertSee('Guru Batch');
});
