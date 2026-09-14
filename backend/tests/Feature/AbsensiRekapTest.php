<?php

use App\Models\Absensi;
use App\Models\Guru;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\WaliMurid;
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

if (! function_exists('rombelRekapUji')) {
    function rombelRekapUji(string $namaKelas = '7A'): Rombel
    {
        return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', $namaKelas))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();
    }
}

if (! function_exists('siswaRekapUji')) {
    function siswaRekapUji(string $nis, string $nama, Rombel $rombel): Siswa
    {
        static $n = 0;
        $n++;

        $user = User::factory()->create(['username' => 'siswa-rekap-'.$n, 'name' => $nama]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'nisn' => sprintf('008030%04d', $n),
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

if (! function_exists('catatRekapUji')) {
    function catatRekapUji(Rombel $rombel, Siswa $siswa, string $tanggal, string $status): void
    {
        Absensi::create([
            'rombel_id' => $rombel->id,
            'siswa_id' => $siswa->id,
            'tanggal' => $tanggal,
            'status' => $status,
            'metode' => 'manual',
            'dicatat_oleh' => superAdmin()->id,
        ]);
    }
}

if (! function_exists('guruWaliUji')) {
    function guruWaliUji(Rombel $rombel): User
    {
        $user = User::factory()->create(['username' => 'wali-uji']);
        $user->assignRole('guru');
        $guru = Guru::create(['user_id' => $user->id, 'nama' => 'Wali Uji', 'jenis_kelamin' => 'L', 'is_aktif' => true]);
        $rombel->update(['wali_guru_id' => $guru->id]);

        return $user;
    }
}

test('tamu tidak dapat membuka rekap', function () {
    $this->get(route('admin.absensis.rekap'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka rekap', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.absensis.rekap'))->assertForbidden();
});

test('rekap mingguan menampilkan matriks dan total', function () {
    $rombel = rombelRekapUji();
    $s1 = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    $s2 = siswaRekapUji('6002', 'Anak Rekap Dua', $rombel);
    catatRekapUji($rombel, $s1, '2026-09-07', 'hadir');
    catatRekapUji($rombel, $s1, '2026-09-08', 'sakit');
    catatRekapUji($rombel, $s2, '2026-09-07', 'alpa');

    $this->actingAs(superAdmin())->get(route('admin.absensis.rekap', [
        'rombel_id' => $rombel->id,
        'periode' => 'minggu',
        'acuan' => '2026-09-09',
    ]))->assertOk()
        ->assertSee('Anak Rekap Satu')
        ->assertSee('H 1')
        ->assertSee('S 1')
        ->assertSee('A 1');
});

test('semester ganjil genap memakai konvensi kalender', function () {
    $tahun = TahunAjaran::aktif()->first();
    $rombel = rombelRekapUji();
    $siswa = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    catatRekapUji($rombel, $siswa, '2025-08-10', 'hadir');
    catatRekapUji($rombel, $siswa, '2026-02-10', 'izin');

    $dasar = ['rombel_id' => $rombel->id, 'tahun_ajaran_id' => $tahun->id];

    $this->actingAs(superAdmin())->get(route('admin.absensis.rekap', $dasar + ['periode' => 'ganjil']))
        ->assertOk()->assertSee('2025-07-01 s.d. 2025-12-31')->assertSee('H 1')->assertDontSee('I 1');

    $this->actingAs(superAdmin())->get(route('admin.absensis.rekap', $dasar + ['periode' => 'genap']))
        ->assertOk()->assertSee('2026-01-01 s.d. 2026-06-30')->assertSee('I 1')->assertDontSee('H 1');
});

test('ekspor excel dan pdf rekap', function () {
    $rombel = rombelRekapUji();
    $siswa = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    catatRekapUji($rombel, $siswa, '2026-09-07', 'hadir');
    $filter = ['rombel_id' => $rombel->id, 'periode' => 'minggu', 'acuan' => '2026-09-09'];

    $this->actingAs(superAdmin())->get(route('admin.absensis.rekap.excel', $filter))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs(superAdmin())->get(route('admin.absensis.rekap.pdf', $filter))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('wali dikunci ke rombel ampuan', function () {
    $rombel7A = rombelRekapUji('7A');
    $rombel7B = rombelRekapUji('7B');
    $wali = guruWaliUji($rombel7A);

    $this->actingAs($wali)->get(route('admin.absensis.rekap'))
        ->assertOk()->assertSee('7A')->assertDontSee('7B');

    $this->actingAs($wali)->get(route('admin.absensis.rekap', ['rombel_id' => $rombel7B->id]))
        ->assertForbidden();
});

test('dashboard ortu menampilkan anak dan status hari ini', function () {
    $rombel = rombelRekapUji();
    $siswa = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    $ortu = User::where('username', 'ortu-'.$siswa->nisn)->firstOrFail();
    catatRekapUji($rombel, $siswa, now()->toDateString(), 'hadir');

    $this->actingAs($ortu)->get(route('ortu.dashboard'))
        ->assertOk()->assertSee('Anak Rekap Satu')->assertSee('Hadir');

    $tautan = WaliMurid::where('user_id', $ortu->id)->where('siswa_id', $siswa->id)->firstOrFail();
    $this->actingAs($ortu)->get(route('ortu.anak', $tautan))
        ->assertOk()->assertSee('Hadir: 1');
});

test('ortu tidak dapat melihat anak keluarga lain', function () {
    $rombel = rombelRekapUji();
    $siswa = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    $lain = siswaRekapUji('6002', 'Anak Rekap Dua', $rombel);
    $ortu = User::where('username', 'ortu-'.$siswa->nisn)->firstOrFail();
    $tautanLain = WaliMurid::where('siswa_id', $lain->id)->firstOrFail();

    $this->actingAs($ortu)->get(route('ortu.anak', $tautanLain))->assertForbidden();
    $this->actingAs($ortu)->get(route('ortu.dashboard'))->assertOk()->assertDontSee('Anak Rekap Dua');
});

test('ortu tidak dapat membuka rekap admin', function () {
    $rombel = rombelRekapUji();
    $siswa = siswaRekapUji('6001', 'Anak Rekap Satu', $rombel);
    $ortu = User::where('username', 'ortu-'.$siswa->nisn)->firstOrFail();

    $this->actingAs($ortu)->get(route('admin.absensis.rekap'))->assertForbidden();
});
