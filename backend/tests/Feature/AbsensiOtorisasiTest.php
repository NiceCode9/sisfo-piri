<?php

use App\Models\Absensi;
use App\Models\Guru;
use App\Models\MataPelajaran;
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
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
});

if (! function_exists('rombelOtorisasiUji')) {
    function rombelOtorisasiUji(string $namaKelas): Rombel
    {
        return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', $namaKelas))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();
    }
}

if (! function_exists('siswaOtorisasiUji')) {
    function siswaOtorisasiUji(string $nis, string $nama, Rombel $rombel, string $status = 'aktif'): Siswa
    {
        static $n = 0;
        $n++;

        $user = User::factory()->create(['username' => 'siswa-oto-'.$n, 'name' => $nama]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'nisn' => sprintf('009030%04d', $n),
            'qr_token' => 'token-oto-'.$n,
            'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
            'kelas_id' => $rombel->kelas_id,
            'is_aktif' => true,
        ]);

        RiwayatKelas::create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $rombel->kelas_id,
            'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
            'status' => $status,
        ]);

        return $siswa;
    }
}

if (! function_exists('guruRoleUji')) {
    function guruRoleUji(string $role, string $nama): User
    {
        $slug = str($role.'-'.$nama)->slug()->value();
        $user = User::factory()->create(['username' => $slug, 'name' => $nama]);
        $user->assignRole($role);
        Guru::create([
            'user_id' => $user->id,
            'nama' => $nama,
            'jenis_kelamin' => 'L',
            'is_aktif' => true,
        ]);

        return $user;
    }
}

if (! function_exists('waliOtorisasiUji')) {
    function waliOtorisasiUji(Rombel $rombel): User
    {
        $user = guruRoleUji('guru', 'Wali '.$rombel->kelas->nama_kelas);
        $rombel->update(['wali_guru_id' => Guru::where('user_id', $user->id)->firstOrFail()->id]);

        return $user;
    }
}

test('wali hanya melihat rombel ampuan sendiri di grid absensi', function () {
    $rombelSendiri = rombelOtorisasiUji('7A');
    rombelOtorisasiUji('7B');
    $wali = waliOtorisasiUji($rombelSendiri);

    $respons = $this->actingAs($wali)->get(route('admin.absensis.index'));
    $respons->assertOk();

    expect(htmlTanpaToken($respons->getContent()))
        ->toContain('7A')
        ->not->toContain('7B');
});

test('wali ditolak membuka grid absensi rombel yang bukan ampunya', function () {
    $rombelSendiri = rombelOtorisasiUji('7A');
    $rombelAsing = rombelOtorisasiUji('7B');
    $wali = waliOtorisasiUji($rombelSendiri);

    $this->actingAs($wali)
        ->get(route('admin.absensis.index', ['rombel_id' => $rombelAsing->id]))
        ->assertForbidden();
});

test('wali ditolak menyimpan batch ke rombel yang bukan ampunya', function () {
    $rombelSendiri = rombelOtorisasiUji('7A');
    $rombelAsing = rombelOtorisasiUji('7B');
    $siswa = siswaOtorisasiUji('6101', 'Anak Kelas Asing', $rombelAsing);
    $wali = waliOtorisasiUji($rombelSendiri);

    // Role `guru` memang tidak memegang `absensis.create`; reviewer perlu melihat
    // bahwa penolakan datang dari cek jangkauan rombel, bukan kebetulan dari
    // middleware permission.
    $wali->givePermissionTo('absensis.create');

    $this->actingAs($wali)
        ->post(route('admin.absensis.batch'), [
            'rombel_id' => $rombelAsing->id,
            'tanggal' => now()->toDateString(),
            'status' => [$siswa->id => 'hadir'],
        ])
        ->assertForbidden();

    expect(Absensi::where('siswa_id', $siswa->id)->exists())->toBeFalse();
});

test('wali ditolak scan QR ke rombel yang bukan ampunya', function () {
    $rombelSendiri = rombelOtorisasiUji('7A');
    $rombelAsing = rombelOtorisasiUji('7B');
    $siswa = siswaOtorisasiUji('6102', 'Anak Scan Asing', $rombelAsing);
    $wali = waliOtorisasiUji($rombelSendiri);
    $wali->givePermissionTo('absensis.create');

    $this->actingAs($wali)
        ->postJson(route('admin.absensis.scan.store'), [
            'token' => $siswa->qr_token,
            'rombel_id' => $rombelAsing->id,
        ])
        ->assertForbidden();

    expect(Absensi::where('siswa_id', $siswa->id)->exists())->toBeFalse();
});

test('pengampu mapel dapat membuka grid rombel yang diampu', function () {
    $rombelSendiri = rombelOtorisasiUji('7C');
    $rombelAsing = rombelOtorisasiUji('7D');
    $user = guruRoleUji('guru', 'Pengampu Uji');

    // AkademikSeeder sudah mengisi pengampu MTK untuk setiap rombel, jadi
    // penugasan uji memakai mapel sendiri agar tidak bentrok pada unique
    // (mata_pelajaran_id, rombel_id).
    $mapel = MataPelajaran::firstOrCreate(
        ['kode' => 'UJI-PENG'],
        ['nama' => 'Mapel Uji Pengampu', 'tingkat' => '7'],
    );

    Pengampu::firstOrCreate([
        'guru_id' => Guru::where('user_id', $user->id)->firstOrFail()->id,
        'rombel_id' => $rombelSendiri->id,
        'mata_pelajaran_id' => $mapel->id,
    ]);

    $respons = $this->actingAs($user)->get(route('admin.absensis.index', ['rombel_id' => $rombelSendiri->id]));
    $respons->assertOk();
    expect(htmlTanpaToken($respons->getContent()))->toContain('7C');

    $this->actingAs($user)
        ->get(route('admin.absensis.index', ['rombel_id' => $rombelAsing->id]))
        ->assertForbidden();
});

test('guru tanpa penugasan melihat daftar kosong bukan seluruh rombel', function () {
    rombelOtorisasiUji('7A');
    rombelOtorisasiUji('7B');
    $user = guruRoleUji('guru', 'Guru Tanpa Kelas');

    $respons = $this->actingAs($user)->get(route('admin.absensis.index'));
    $respons->assertOk();

    expect(htmlTanpaToken($respons->getContent()))
        ->not->toContain('7A')
        ->not->toContain('7B');
});

test('guru piket tetap dapat mencatat di seluruh rombel', function () {
    $rombel = rombelOtorisasiUji('7B');
    $siswa = siswaOtorisasiUji('6104', 'Anak Piket', $rombel);
    $piket = guruRoleUji('guru-piket', 'Guru Piket Uji');

    $respons = $this->actingAs($piket)->get(route('admin.absensis.index'));
    $respons->assertOk();
    expect(htmlTanpaToken($respons->getContent()))->toContain('7B');

    $this->actingAs($piket)
        ->post(route('admin.absensis.batch'), [
            'rombel_id' => $rombel->id,
            'tanggal' => now()->toDateString(),
            'status' => [$siswa->id => 'hadir'],
        ])
        ->assertRedirect();

    expect(Absensi::where('siswa_id', $siswa->id)->exists())->toBeTrue();
});

test('cakupan absensi guru piket tidak meluas ke modul materi dan tugas', function () {
    $piket = guruRoleUji('guru-piket', 'Guru Piket Modul');

    $this->actingAs($piket)->get(route('admin.materis.index'))->assertForbidden();
    $this->actingAs($piket)->get(route('admin.tugas.index'))->assertForbidden();
});
