<?php

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Pengaturan;
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

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

if (! function_exists('buatAnggotaRombel')) {
    function buatAnggotaRombel(string $nis, string $nama, ?Rombel $rombel = null): Siswa
    {
        static $n = 0;
        $n++;

        $rombel ??= Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();

        $user = User::factory()->create(['username' => 'siswa-absen-'.$n, 'name' => $nama]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'nisn' => sprintf('008010%04d', $n),
            'qr_token' => 'token-'.$n.'-'.str()->random(8),
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

if (! function_exists('rombelUjiAbsensi')) {
    function rombelUjiAbsensi(): Rombel
    {
        return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();
    }
}

test('tamu tidak dapat membuka absensi', function () {
    $this->get(route('admin.absensis.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka absensi', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.absensis.index'))->assertForbidden();
});

test('grid menampilkan siswa rombel', function () {
    $rombel = rombelUjiAbsensi();
    buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    $this->actingAs(superAdmin())->get(route('admin.absensis.index', ['rombel_id' => $rombel->id]))
        ->assertOk()->assertSee('Anak Absen Satu');
});

test('batch manual tersimpan dan dapat diperbarui', function () {
    $rombel = rombelUjiAbsensi();
    $s1 = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $s2 = buatAnggotaRombel('4002', 'Anak Absen Dua', $rombel);
    $hari = now()->toDateString();

    $payload = [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$s1->id => 'hadir', $s2->id => 'sakit'],
    ];

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), $payload)
        ->assertRedirect(route('admin.absensis.index', ['rombel_id' => $rombel->id, 'tanggal' => $hari]));

    expect(Absensi::where('tanggal', $hari)->count())->toBe(2)
        ->and(Absensi::where('siswa_id', $s2->id)->first()->status)->toBe('sakit')
        ->and(Absensi::where('siswa_id', $s1->id)->first()->metode)->toBe('manual');

    $payload['status'] = [$s1->id => 'izin', $s2->id => 'sakit'];
    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), $payload)->assertSessionHas('success');

    expect(Absensi::where('tanggal', $hari)->count())->toBe(2)
        ->and(Absensi::where('siswa_id', $s1->id)->first()->status)->toBe('izin');
});

test('batch menolak siswa luar rombel', function () {
    $rombel = rombelUjiAbsensi();
    $luar = buatAnggotaRombel('4009', 'Anak Luar');

    $kelas7B = Kelas::where('nama_kelas', '7B')->firstOrFail();
    $rombel7B = Rombel::where('kelas_id', $kelas7B->id)->where('tahun_ajaran_id', $rombel->tahun_ajaran_id)->firstOrFail();
    $luar->update(['kelas_id' => $kelas7B->id]);
    $luar->riwayatKelas()->delete();
    RiwayatKelas::create(['siswa_id' => $luar->id, 'kelas_id' => $kelas7B->id, 'tahun_ajaran_id' => $rombel->tahun_ajaran_id, 'status' => 'aktif']);

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => now()->toDateString(),
        'status' => [$luar->id => 'hadir'],
    ])->assertSessionHasErrors('status.'.$luar->id);

    expect(Absensi::count())->toBe(0);
    expect($rombel7B->id)->not->toBe($rombel->id);
});

test('scan sukses mencatat hadir dengan jam', function () {
    Pengaturan::updateOrCreate(['kunci' => 'batas_terlambat'], ['nilai' => '23:59']);
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    $response = $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), [
        'token' => $siswa->qr_token,
        'rombel_id' => $rombel->id,
    ]);

    $response->assertOk()->assertJson(['status' => 'hadir', 'baru' => true]);
    expect(Absensi::where('siswa_id', $siswa->id)->first()->metode)->toBe('qr');
});

test('scan lewat batas tercatat terlambat', function () {
    Pengaturan::updateOrCreate(['kunci' => 'batas_terlambat'], ['nilai' => '00:00']);
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), [
        'token' => $siswa->qr_token,
        'rombel_id' => $rombel->id,
    ])->assertOk()->assertJson(['status' => 'terlambat']);
});

test('scan token asing ditolak', function () {
    $rombel = rombelUjiAbsensi();

    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), [
        'token' => 'token-tidak-ada',
        'rombel_id' => $rombel->id,
    ])->assertUnprocessable()->assertJson(['message' => 'QR tidak dikenal.']);
});

test('scan ulang hari sama tidak duplikat', function () {
    Pengaturan::updateOrCreate(['kunci' => 'batas_terlambat'], ['nilai' => '23:59']);
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $payload = ['token' => $siswa->qr_token, 'rombel_id' => $rombel->id];

    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), $payload)->assertOk();
    $this->actingAs(superAdmin())->postJson(route('admin.absensis.scan.store'), $payload)
        ->assertOk()->assertJson(['baru' => false]);

    expect(Absensi::where('siswa_id', $siswa->id)->count())->toBe(1);
});

test('generate ulang token QR menghanguskan token lama', function () {
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu');
    $lama = $siswa->qr_token;

    $this->actingAs(superAdmin())->post(route('admin.siswas.qr', $siswa))->assertSessionHas('success');

    expect($siswa->fresh()->qr_token)->not->toBe($lama);
});

test('kartu menampilkan QR dari token', function () {
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu');

    $this->actingAs(superAdmin())->get(route('admin.siswas.kartu', $siswa))
        ->assertOk()->assertSee('<svg', false);
});
