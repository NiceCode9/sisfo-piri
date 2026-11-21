<?php

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\TokenKartuSiswa;
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

if (! function_exists('rombelAbsensiKartu')) {
    function rombelAbsensiKartu(string $kelas = '7A'): Rombel
    {
        return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', $kelas))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();
    }
}

if (! function_exists('siswaAbsensiKartu')) {
    function siswaAbsensiKartu(string $nis, string $nama, ?Rombel $rombel = null): Siswa
    {
        static $n = 0;
        $n++;

        $rombel ??= rombelAbsensiKartu();

        $user = User::factory()->create(['username' => 'siswa-kartu-'.$n, 'name' => $nama]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'nisn' => sprintf('008040%04d', $n),
            'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
            'kelas_id' => $rombel->kelas_id,
            'is_aktif' => true,
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

if (! function_exists('guruAbsensiKartu')) {
    function guruAbsensiKartu(?Rombel $wali = null, ?Rombel $ampu = null, string $nama = 'Guru Absensi'): User
    {
        static $n = 0;
        $n++;

        $user = User::factory()->create(['username' => 'guru-kartu-'.$n, 'name' => $nama]);
        $user->assignRole('guru');

        $guru = Guru::create([
            'user_id' => $user->id,
            'nama' => $nama,
            'jenis_kelamin' => 'L',
            'is_aktif' => true,
        ]);

        $wali?->update(['wali_guru_id' => $guru->id]);

        if ($ampu !== null) {
            Pengampu::create([
                'guru_id' => $guru->id,
                'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
                'rombel_id' => $ampu->id,
            ]);
        }

        return $user;
    }
}

if (! function_exists('guruTanpaKelasAbsensi')) {
    function guruTanpaKelasAbsensi(): User
    {
        return guruAbsensiKartu(nama: 'Guru Tanpa Kelas');
    }
}

if (! function_exists('jamAbsensiKartu')) {
    function jamAbsensiKartu(int $jam, int $menit): void
    {
        test()->travelTo(now()->startOfDay()->setTime($jam, $menit));
    }
}

test('rombel aktif siswa dibaca dari riwayat kelas, bukan kelas_id', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7001', 'Anak Riwayat', $rombel);

    $kelasLain = Kelas::where('nama_kelas', '7B')->firstOrFail();
    $siswa->update(['kelas_id' => $kelasLain->id]);

    expect(Rombel::aktifUntukSiswa($siswa)?->id)->toBe($rombel->id);

    $siswa->riwayatKelas()->update(['status' => 'pindah']);

    expect(Rombel::aktifUntukSiswa($siswa))->toBeNull();
});

test('token kartu siswa hanya berlaku untuk id yang ditandatangani', function () {
    $siswa = siswaAbsensiKartu('7002', 'Anak Token');
    $token = TokenKartuSiswa::generate($siswa->id);

    expect(TokenKartuSiswa::baca($token))->toBe($siswa->id)
        ->and(TokenKartuSiswa::baca($token.'x'))->toBeNull()
        ->and(TokenKartuSiswa::baca('abskartu|'.$siswa->id.'|palsu'))->toBeNull()
        ->and(TokenKartuSiswa::baca('bukan-token'))->toBeNull();
});

test('kartu pelajar memuat QR token siswa', function () {
    $siswa = siswaAbsensiKartu('7003', 'Anak Kartu');

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin)
        ->get(route('admin.siswas.kartu', $siswa))
        ->assertOk()
        ->assertSee('id="qr-kartu"', false)
        ->assertSee('<svg', false);
});
