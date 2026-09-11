<?php

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

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

test('tamu tidak dapat membuka daftar siswa', function () {
    $this->get(route('admin.siswas.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka daftar siswa', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.siswas.index'))->assertForbidden();
});

test('super-admin dapat membuka daftar siswa', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.siswas.index'));
    $response->assertOk()->assertSee('Daftar Siswa');
});

test('super-admin dapat menambah siswa beserta akun dan ortu', function () {
    $kelas = Kelas::where('nama_kelas', '7A')->first();

    $response = $this->actingAs(superAdmin())->post(route('admin.siswas.store'), [
        'nis' => '1001',
        'nisn' => '0070012001',
        'nama' => 'Siswa Manual',
        'kelas_id' => $kelas->id,
        'is_aktif' => 1,
        'nama_ayah' => 'Ayah Manual',
        'pekerjaan_ayah' => 'Wiraswasta',
        'nama_ibu' => 'Ibu Manual',
        'pekerjaan_ibu' => 'IRT',
        'no_hp_orang_tua' => '081234567890',
    ]);

    $response->assertRedirect(route('admin.siswas.index'));

    $siswa = Siswa::where('nisn', '0070012001')->first();
    expect($siswa)->not->toBeNull()
        ->and($siswa->nama_ayah)->toBe('Ayah Manual')
        ->and($siswa->riwayatKelas()->count())->toBe(1);

    $user = User::where('username', '0070012001')->first();
    expect($user)->not->toBeNull()
        ->and(Hash::check('0070012001', $user->password))->toBeTrue()
        ->and($user->hasRole('siswa'))->toBeTrue();
});

test('nisn yang sudah dipakai akun ditolak', function () {
    User::factory()->create(['username' => '0070012001']);

    $response = $this->actingAs(superAdmin())->post(route('admin.siswas.store'), [
        'nisn' => '0070012001',
        'nama' => 'Duplikat',
        'is_aktif' => 1,
    ]);

    $response->assertSessionHasErrors('nisn');
});

test('show siswa tampilkan profil ortu riwayat dan tagihan', function () {
    $kelas = Kelas::where('nama_kelas', '7A')->first();
    $user = User::factory()->create(['username' => '0070012001', 'name' => 'Siswa Show']);
    $user->assignRole('siswa');
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nis' => '1001',
        'nisn' => '0070012001',
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'kelas_id' => $kelas->id,
        'is_aktif' => true,
        'nama_ayah' => 'Ayah Show',
    ]);

    $response = $this->actingAs(superAdmin())->get(route('admin.siswas.show', $siswa));

    $response->assertOk();
    foreach (['Profil Siswa', 'Data Orang Tua', 'Riwayat Kelas', 'Tagihan', 'Ayah Show'] as $marker) {
        $response->assertSee($marker, false);
    }
});

test('edit ganti kelas otomatis catat riwayat', function () {
    $kelasA = Kelas::where('nama_kelas', '7A')->first();
    $kelasB = Kelas::where('nama_kelas', '7B')->first();
    $user = User::factory()->create(['username' => '0070012001', 'name' => 'Siswa Pindah']);
    $user->assignRole('siswa');
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nisn' => '0070012001',
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'kelas_id' => $kelasA->id,
        'is_aktif' => true,
    ]);

    $this->actingAs(superAdmin())->put(route('admin.siswas.update', $siswa), [
        'nisn' => '0070012001',
        'nama' => 'Siswa Pindah',
        'kelas_id' => $kelasB->id,
        'is_aktif' => 1,
    ])->assertRedirect(route('admin.siswas.show', $siswa));

    expect($siswa->fresh()->kelas_id)->toBe($kelasB->id)
        ->and($siswa->riwayatKelas()->count())->toBe(1)
        ->and($siswa->riwayatKelas()->first()->kelas_id)->toBe($kelasB->id);
});

test('destroy diblokir bila ada riwayat kelas', function () {
    $kelas = Kelas::where('nama_kelas', '7A')->first();
    $user = User::factory()->create(['username' => '0070012001']);
    $user->assignRole('siswa');
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nisn' => '0070012001',
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'kelas_id' => $kelas->id,
        'is_aktif' => true,
    ]);
    $siswa->riwayatKelas()->create([
        'kelas_id' => $kelas->id,
        'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
        'status' => 'aktif',
    ]);

    $this->actingAs(superAdmin())->delete(route('admin.siswas.destroy', $siswa))
        ->assertSessionHas('error');

    expect(Siswa::find($siswa->id))->not->toBeNull();
});

test('destroy tanpa relasi hapus siswa dan akun', function () {
    $user = User::factory()->create(['username' => '0070012001']);
    $user->assignRole('siswa');
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nisn' => '0070012001',
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'is_aktif' => true,
    ]);

    $this->actingAs(superAdmin())->delete(route('admin.siswas.destroy', $siswa))
        ->assertRedirect(route('admin.siswas.index'));

    expect(Siswa::find($siswa->id))->toBeNull()
        ->and(User::find($user->id))->toBeNull();
});

test('admin tanpa permission delete tidak dapat menghapus siswa', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create(['username' => '0070012001']);
    $user->assignRole('siswa');
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nisn' => '0070012001',
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'is_aktif' => true,
    ]);

    $this->actingAs($admin)->delete(route('admin.siswas.destroy', $siswa))->assertForbidden();
});

test('halaman kartu siswa tampilkan data asli', function () {
    $kelas = Kelas::where('nama_kelas', '7A')->first();
    $user = User::factory()->create(['username' => '0070012001', 'name' => 'Siswa Kartu']);
    $user->assignRole('siswa');
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nisn' => '0070012001',
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'kelas_id' => $kelas->id,
        'is_aktif' => true,
    ]);

    $response = $this->actingAs(superAdmin())->get(route('admin.siswas.kartu', $siswa));

    $response->assertOk();
    foreach (['Siswa Kartu', '0070012001', '7A', 'KARTU PELAJAR'] as $marker) {
        $response->assertSee($marker, false);
    }
});

test('tamu dan tanpa permission ditolak kartu dan template', function () {
    $user = User::factory()->create(['username' => '0070012001']);
    $user->assignRole('siswa');
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nisn' => '0070012001',
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'is_aktif' => true,
    ]);

    $this->get(route('admin.siswas.kartu', $siswa))->assertRedirect(route('login'));
    $this->get(route('admin.siswas.template'))->assertRedirect(route('login'));

    $polos = User::factory()->create();
    $this->actingAs($polos)->get(route('admin.siswas.kartu', $siswa))->assertForbidden();
});

test('template excel dapat diunduh', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.siswas.template'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheetml');
});

test('import excel buat akun dan riwayat, baris gagal dilaporkan', function () {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        ['nis', 'nisn', 'nama', 'kelas', 'tahun_ajaran', 'nama_ayah', 'pekerjaan_ayah', 'nama_ibu', 'pekerjaan_ibu', 'no_hp_orang_tua'],
        ['2001', '0070022001', 'Import Satu', '7A', '2026/2027', 'Ayah Satu', 'Wiraswasta', 'Ibu Satu', 'IRT', '081234567890'],
        ['2002', '', 'Import Gagal', '7A', '2026/2027', '', '', '', '', ''],
    ]);
    // Sel NIS/NISN bertipe teks eksplisit agar 0 di depan tidak hilang.
    foreach (['A2', 'A3', 'B2', 'B3'] as $cell) {
        $sheet->getCell($cell)->setValueExplicit(
            $sheet->getCell($cell)->getValue(),
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
        );
    }
    $path = tempnam(sys_get_temp_dir(), 'siswa').'.xlsx';
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
    $file = new UploadedFile($path, 'siswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', UPLOAD_ERR_OK, true);

    $response = $this->actingAs(superAdmin())->post(route('admin.siswas.import'), [
        'file' => $file,
    ]);

    $response->assertSessionHas('error');
    expect(Siswa::where('nisn', '0070022001')->exists())->toBeTrue()
        ->and(User::where('username', '0070022001')->first()->hasRole('siswa'))->toBeTrue();
});
