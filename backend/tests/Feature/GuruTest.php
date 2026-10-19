<?php

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

/**
 * Buat berkas Excel upload dari baris [nip, nama, jenis_kelamin, telp, alamat, is_aktif].
 *
 * @param  array<int, array<int, string|int>>  $baris
 */
function fileImportGuru(array $baris): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        ['nip', 'nama', 'jenis_kelamin', 'telp', 'alamat', 'is_aktif'],
        ...$baris,
    ], null, 'A1');

    $path = tempnam(sys_get_temp_dir(), 'import-guru-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'guru.xlsx', null, null, true);
}

test('tamu tidak dapat membuka daftar guru', function () {
    $this->get(route('admin.gurus.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka guru', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.gurus.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar guru', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.gurus.index'));
    $response->assertOk()->assertSee('Daftar Guru');
});

test('super-admin dapat menambah guru beserta akun', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.gurus.store'), [
        'nip' => '196501011990031001',
        'nama' => 'Guru Satu',
        'jenis_kelamin' => 'L',
        'telp' => '081234567890',
        'alamat' => 'Jl Guru 1',
        'is_aktif' => 1,
    ]);

    $response->assertRedirect(route('admin.gurus.index'));
    $guru = Guru::where('nip', '196501011990031001')->first();
    expect($guru)->not->toBeNull()
        ->and($guru->user)->not->toBeNull()
        ->and($guru->user->username)->toBe('081234567890')
        ->and(Hash::check('password', $guru->user->password))->toBeTrue()
        ->and($guru->user->hasRole('guru'))->toBeTrue();
});

test('telp yang sudah dipakai username akun lain ditolak saat tambah guru', function () {
    User::factory()->create(['username' => '081234567890']);

    $response = $this->actingAs(superAdmin())->post(route('admin.gurus.store'), [
        'nip' => '196501011990031001',
        'nama' => 'Guru Duplikat',
        'jenis_kelamin' => 'L',
        'telp' => '081234567890',
        'is_aktif' => 1,
    ]);

    $response->assertSessionHasErrors('telp');
});

test('super-admin dapat memperbarui guru dan akun ikut disetel ulang', function () {
    $user = User::factory()->create(['username' => '196501011990031001', 'password' => 'rahasia123']);
    $guru = Guru::create(['user_id' => $user->id, 'nama' => 'Guru Dua', 'jenis_kelamin' => 'P', 'is_aktif' => true]);

    $response = $this->actingAs(superAdmin())->put(route('admin.gurus.update', $guru), [
        'nama' => 'Guru Dua Baru',
        'jenis_kelamin' => 'P',
        'telp' => '081234567891',
        'is_aktif' => 1,
    ]);

    $response->assertRedirect(route('admin.gurus.index'));
    $user = $user->fresh();
    expect($guru->fresh()->nama)->toBe('Guru Dua Baru')
        ->and($user->username)->toBe('081234567891')
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('tidak dapat menghapus guru yang masih ditugaskan', function () {
    $user = User::factory()->create(['username' => 'guru03']);
    $guru = Guru::create(['user_id' => $user->id, 'nama' => 'Guru Tiga', 'jenis_kelamin' => 'L', 'is_aktif' => true]);
    $rombel = Rombel::create([
        'kelas_id' => Kelas::create(['nama_kelas' => '7Z', 'tingkat' => '7'])->id,
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
    ]);
    $guru->pengampus()->create([
        'mata_pelajaran_id' => MataPelajaran::create(['kode' => 'TST', 'nama' => 'Test', 'kelompok' => 'A', 'kkm' => 75])->id,
        'rombel_id' => $rombel->id,
    ]);

    $response = $this->actingAs(superAdmin())->delete(route('admin.gurus.destroy', $guru));

    $response->assertSessionHas('error');
    expect(Guru::find($guru->id))->not->toBeNull();
});

test('admin tanpa permission delete tidak dapat menghapus guru', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create(['username' => 'guru04']);
    $guru = Guru::create(['user_id' => $user->id, 'nama' => 'Guru Empat', 'jenis_kelamin' => 'L', 'is_aktif' => true]);

    $this->actingAs($admin)->delete(route('admin.gurus.destroy', $guru))->assertForbidden();
    expect(Guru::find($guru->id))->not->toBeNull();
});

test('super-admin dapat mengunduh template import guru', function () {
    $this->actingAs(superAdmin())->get(route('admin.gurus.template'))
        ->assertOk()
        ->assertDownload('template-import-guru.xlsx');
});

test('import guru membuat akun dengan no hp sebagai username', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.gurus.import'), [
        'file' => fileImportGuru([
            ['196501011990031001', 'Guru Satu', 'L', '081234567890', 'Jl Guru 1', 1],
            ['197001012000042002', 'Guru Dua', 'P', '081234567891', '', 1],
        ]),
    ]);

    $response->assertRedirect(route('admin.gurus.index'))->assertSessionHas('success');
    expect(Guru::count())->toBe(2);

    foreach (['196501011990031001' => '081234567890', '197001012000042002' => '081234567891'] as $nip => $telp) {
        $guru = Guru::where('nip', $nip)->first();
        $userakun = User::where('username', $telp)->first();

        expect($guru)->not->toBeNull()->and($guru->telp)->toBe($telp)
            ->and($userakun)->not->toBeNull()
            ->and($userakun->hasRole('guru'))->toBeTrue()
            ->and(Hash::check('password', $userakun->password))->toBeTrue();
    }
});

test('import guru memperbarui nip terdaftar dan akun ikut disetel ulang', function () {
    $user = User::factory()->create(['username' => '196501011990031001', 'password' => 'rahasia123']);
    $guru = Guru::create(['user_id' => $user->id, 'nip' => '196501011990031001', 'nama' => 'Guru Lama', 'jenis_kelamin' => 'L', 'is_aktif' => true]);

    $response = $this->actingAs(superAdmin())->post(route('admin.gurus.import'), [
        'file' => fileImportGuru([
            ['196501011990031001', 'Guru Baru', 'L', '081234567891', '', 1],
        ]),
    ]);

    $response->assertRedirect(route('admin.gurus.index'))->assertSessionHas('success');

    $user = $user->fresh();
    expect($guru->fresh()->nama)->toBe('Guru Baru')
        ->and($guru->fresh()->telp)->toBe('081234567891')
        ->and(Guru::count())->toBe(1)
        ->and($user->username)->toBe('081234567891')
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('import guru menolak no hp yang sudah dipakai username akun lain', function () {
    User::factory()->create(['username' => '081234567891']);

    $response = $this->actingAs(superAdmin())->post(route('admin.gurus.import'), [
        'file' => fileImportGuru([
            ['196501011990031001', 'Guru Bentrok', 'L', '081234567891', '', 1],
        ]),
    ]);

    $response->assertSessionHas('error');
    expect(Guru::where('nama', 'Guru Bentrok')->exists())->toBeFalse();
});

test('import guru menolak baris nip bukan angka', function () {
    $response = $this->actingAs(superAdmin())->post(route('admin.gurus.import'), [
        'file' => fileImportGuru([
            ['196501011990031001', 'Guru Sah', 'L', '081234567890', '', 1],
            ['AB12', 'Guru NIP Salah', 'P', '081234567891', '', 1],
        ]),
    ]);

    $response->assertSessionHas('error');
    expect(Guru::where('nama', 'Guru NIP Salah')->exists())->toBeFalse()
        ->and(Guru::where('nama', 'Guru Sah')->exists())->toBeTrue();
});
