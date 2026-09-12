<?php

use App\Models\BiayaPendaftaran;
use App\Models\CalonSiswa;
use App\Models\Gelombang;
use App\Models\Guru;
use App\Models\JadwalPpdb;
use App\Models\JalurPendaftaran;
use App\Models\Kelas;
use App\Models\KuotaPendaftaran;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\Pengumuman;
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

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

if (! function_exists('tahunLampauFilter')) {
    function tahunLampauFilter(): TahunAjaran
    {
        return TahunAjaran::where('nama_tahun_ajaran', '2025/2026')->firstOrFail();
    }
}

if (! function_exists('rombelLampauFilter')) {
    function rombelLampauFilter(): Rombel
    {
        $kelas = Kelas::firstOrCreate(['nama_kelas' => '8Z'], ['tingkat' => '8']);

        return Rombel::firstOrCreate([
            'kelas_id' => $kelas->id,
            'tahun_ajaran_id' => tahunLampauFilter()->id,
        ]);
    }
}

test('rombel default tahun aktif, semua tampil, spesifik tahun lampau', function () {
    rombelLampauFilter();

    $this->actingAs(superAdmin())->get(route('admin.rombels.index'))
        ->assertOk()->assertSee('7A')->assertDontSee('8Z');

    $this->actingAs(superAdmin())->get(route('admin.rombels.index', ['tahun' => 'semua']))
        ->assertOk()->assertSee('7A')->assertSee('8Z');

    $this->actingAs(superAdmin())->get(route('admin.rombels.index', ['tahun' => tahunLampauFilter()->id]))
        ->assertOk()->assertSee('8Z')->assertDontSee('7A');
});

test('pengampu default tahun aktif, semua tampil, spesifik tahun lampau', function () {
    $user = User::create(['name' => 'Guru Lampau', 'username' => 'guru-lampau', 'email' => 'guru-lampau@example.com', 'password' => 'password']);
    $user->assignRole('guru');
    $guru = Guru::create(['user_id' => $user->id, 'nip' => '999000000000000001', 'nama' => 'Guru Lampau', 'jenis_kelamin' => 'L', 'is_aktif' => true]);
    Pengampu::create([
        'guru_id' => $guru->id,
        'mata_pelajaran_id' => MataPelajaran::where('kode', 'MTK')->firstOrFail()->id,
        'rombel_id' => rombelLampauFilter()->id,
    ]);

    $this->actingAs(superAdmin())->get(route('admin.pengampus.index'))
        ->assertOk()->assertSee('Guru A')->assertDontSee('Guru Lampau');

    $this->actingAs(superAdmin())->get(route('admin.pengampus.index', ['tahun' => 'semua']))
        ->assertOk()->assertSee('Guru Lampau');

    $this->actingAs(superAdmin())->get(route('admin.pengampus.index', ['tahun' => tahunLampauFilter()->id]))
        ->assertOk()->assertSee('Guru Lampau')->assertDontSee('Guru A');
});

test('siswa default tahun aktif, semua tampil, spesifik tahun lampau', function () {
    $kelas = Kelas::where('nama_kelas', '7A')->firstOrFail();
    Siswa::create(['nis' => '8880001', 'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id, 'kelas_id' => $kelas->id, 'is_aktif' => true]);
    Siswa::create(['nis' => '9990001', 'tahun_ajaran_id' => tahunLampauFilter()->id, 'kelas_id' => $kelas->id, 'is_aktif' => true]);

    $this->actingAs(superAdmin())->get(route('admin.siswas.index'))
        ->assertOk()->assertSee('8880001')->assertDontSee('9990001');

    $this->actingAs(superAdmin())->get(route('admin.siswas.index', ['tahun' => 'semua']))
        ->assertOk()->assertSee('8880001')->assertSee('9990001');

    $this->actingAs(superAdmin())->get(route('admin.siswas.index', ['tahun' => tahunLampauFilter()->id]))
        ->assertOk()->assertSee('9990001')->assertDontSee('8880001');
});

test('kenaikan kelas default tahun aktif, semua tampil', function () {
    $kelas = Kelas::where('nama_kelas', '7A')->firstOrFail();
    Siswa::create(['nis' => '8880002', 'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id, 'kelas_id' => $kelas->id, 'is_aktif' => true]);
    Siswa::create(['nis' => '9990002', 'tahun_ajaran_id' => tahunLampauFilter()->id, 'kelas_id' => $kelas->id, 'is_aktif' => true]);

    $this->actingAs(superAdmin())->get(route('admin.kenaikan.index'))
        ->assertOk()->assertSee('8880002')->assertDontSee('9990002');

    $this->actingAs(superAdmin())->get(route('admin.kenaikan.index', ['tahun' => 'semua']))
        ->assertOk()->assertSee('9990002');
});

test('calon siswa default tahun aktif, semua tampil, spesifik tahun lampau', function () {
    $jalur = JalurPendaftaran::firstOrFail();
    $data = [
        'jalur_pendaftaran_id' => $jalur->id,
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Test',
        'tanggal_lahir' => '2010-01-01',
        'agama' => 'Islam',
        'alamat' => 'Jl Test',
        'status_pendaftaran' => 'menunggu',
    ];
    CalonSiswa::create($data + [
        'tahun_ajaran_id' => TahunAjaran::aktif()->first()->id,
        'no_pendaftaran' => 'PPDB-2026-0901',
        'nik' => '1111111111111111',
        'nama_lengkap' => 'Calon Aktif',
    ]);
    CalonSiswa::create($data + [
        'tahun_ajaran_id' => tahunLampauFilter()->id,
        'no_pendaftaran' => 'PPDB-2025-0901',
        'nik' => '2222222222222222',
        'nama_lengkap' => 'Calon Lampau',
    ]);

    $this->actingAs(superAdmin())->get(route('admin.calon-siswas.index'))
        ->assertOk()->assertSee('Calon Aktif')->assertDontSee('Calon Lampau');

    $this->actingAs(superAdmin())->get(route('admin.calon-siswas.index', ['tahun' => 'semua']))
        ->assertOk()->assertSee('Calon Lampau');

    $this->actingAs(superAdmin())->get(route('admin.calon-siswas.index', ['tahun' => tahunLampauFilter()->id]))
        ->assertOk()->assertSee('Calon Lampau')->assertDontSee('Calon Aktif');
});

test('biaya default tahun aktif, semua tampil', function () {
    BiayaPendaftaran::create([
        'tahun_ajaran_id' => tahunLampauFilter()->id,
        'jenis_biaya' => 'Biaya Lampau',
        'jumlah' => 100000,
        'mata_uang' => 'IDR',
        'wajib_bayar' => true,
        'dapat_diangsur' => false,
    ]);

    $this->actingAs(superAdmin())->get(route('admin.biaya-pendaftarans.index'))
        ->assertOk()->assertDontSee('Biaya Lampau');

    $this->actingAs(superAdmin())->get(route('admin.biaya-pendaftarans.index', ['tahun' => 'semua']))
        ->assertOk()->assertSee('Biaya Lampau');
});

test('gelombang default tahun aktif, semua tampil', function () {
    Gelombang::create([
        'tahun_ajaran_id' => tahunLampauFilter()->id,
        'nama_gelombang' => 'Gelombang Lampau',
        'nomor_urut' => 77,
        'tanggal_buka' => '2025-09-01',
        'tanggal_tutup' => '2025-09-30',
        'kuota' => 50,
        'terisi' => 0,
        'is_aktif' => true,
    ]);

    $this->actingAs(superAdmin())->get(route('admin.gelombangs.index'))
        ->assertOk()->assertDontSee('Gelombang Lampau');

    $this->actingAs(superAdmin())->get(route('admin.gelombangs.index', ['tahun' => 'semua']))
        ->assertOk()->assertSee('Gelombang Lampau');
});

test('pengumuman default tahun aktif, semua tampil', function () {
    Pengumuman::create([
        'tahun_ajaran_id' => tahunLampauFilter()->id,
        'judul' => 'Pengumuman Lampau',
        'isi' => 'Isi',
        'tanggal_pengumuman' => '2025-05-01',
        'status_aktif' => true,
    ]);

    $this->actingAs(superAdmin())->get(route('admin.pengumumans.index'))
        ->assertOk()->assertDontSee('Pengumuman Lampau');

    $this->actingAs(superAdmin())->get(route('admin.pengumumans.index', ['tahun' => 'semua']))
        ->assertOk()->assertSee('Pengumuman Lampau');
});

test('jadwal default tahun aktif, semua tampil', function () {
    JadwalPpdb::create([
        'tahun_ajaran_id' => tahunLampauFilter()->id,
        'nama_jadwal' => 'Jadwal Lampau',
        'tanggal_mulai' => '2025-09-01',
        'tanggal_selesai' => '2025-09-30',
    ]);

    $this->actingAs(superAdmin())->get(route('admin.jadwal-ppdbs.index'))
        ->assertOk()->assertDontSee('Jadwal Lampau');

    $this->actingAs(superAdmin())->get(route('admin.jadwal-ppdbs.index', ['tahun' => 'semua']))
        ->assertOk()->assertSee('Jadwal Lampau');
});

test('kuota default tahun aktif, semua tampil', function () {
    $jalur = JalurPendaftaran::create(['nama_jalur' => 'Jalur Lampau', 'aktif' => true]);
    KuotaPendaftaran::create([
        'tahun_ajaran_id' => tahunLampauFilter()->id,
        'jalur_pendaftaran_id' => $jalur->id,
        'kuota' => 10,
        'terisi' => 0,
    ]);

    $this->actingAs(superAdmin())->get(route('admin.kuota-pendaftarans.index'))
        ->assertOk()->assertDontSee('Jalur Lampau');

    $this->actingAs(superAdmin())->get(route('admin.kuota-pendaftarans.index', ['tahun' => 'semua']))
        ->assertOk()->assertSee('Jalur Lampau');
});
