<?php

use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
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

if (! function_exists('buatSiswaAktif')) {
    function buatSiswaAktif(array $overrides = []): Siswa
    {
        static $n = 0;
        $n++;

        $tahun = TahunAjaran::aktif()->first();
        $kelas = Kelas::firstOrCreate(['nama_kelas' => '7A'], ['tingkat' => '7']);

        return Siswa::create(array_merge([
            'nis' => sprintf('100%04d', $n),
            'tahun_ajaran_id' => $tahun->id,
            'kelas_id' => $kelas->id,
            'is_aktif' => true,
        ], $overrides));
    }
}

if (! function_exists('buatTahunTujuan')) {
    function buatTahunTujuan(): TahunAjaran
    {
        return TahunAjaran::create([
            'nama_tahun_ajaran' => '2027/2028',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'status_aktif' => false,
        ]);
    }
}

if (! function_exists('buatKelasNaik')) {
    function buatKelasNaik(): array
    {
        $kelas7 = Kelas::firstOrCreate(['nama_kelas' => '7A'], ['tingkat' => '7']);
        $kelas8 = Kelas::firstOrCreate(['nama_kelas' => '8A'], ['tingkat' => '8']);
        $kelas9 = Kelas::firstOrCreate(['nama_kelas' => '9A'], ['tingkat' => '9']);

        return compact('kelas7', 'kelas8', 'kelas9');
    }
}

test('tamu tidak dapat membuka kenaikan kelas', function () {
    $this->get(route('admin.kenaikan.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka kenaikan', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.kenaikan.index'))->assertForbidden();
});

test('super-admin dapat membuka wizard langkah 1', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.kenaikan.index'));
    $response->assertOk()->assertSee('Langkah 1');
});

test('pratinjau memetakan otomatis dan menandai tingkat akhir lulus', function () {
    $k = buatKelasNaik();
    $tujuan = buatTahunTujuan();
    buatSiswaAktif(['kelas_id' => $k['kelas7']->id]);
    buatSiswaAktif(['kelas_id' => $k['kelas9']->id]);

    $response = $this->actingAs(superAdmin())->get(route('admin.kenaikan.index', [
        'tahun_asal_id' => TahunAjaran::aktif()->first()->id,
        'tahun_tujuan_id' => $tujuan->id,
    ]));

    $response->assertOk()
        ->assertSee('Langkah 2')
        ->assertSee('Naik ke 8A')
        ->assertSee('Lulus');
});

test('proses bulk menaikkan multi kelas sekaligus', function () {
    $k = buatKelasNaik();
    $tujuan = buatTahunTujuan();
    $asal = TahunAjaran::aktif()->first()->id;
    $s1 = buatSiswaAktif(['kelas_id' => $k['kelas7']->id]);
    $s2 = buatSiswaAktif(['kelas_id' => $k['kelas8']->id]);
    $kelas9b = Kelas::create(['nama_kelas' => '9B', 'tingkat' => '9']);

    $response = $this->actingAs(superAdmin())->post(route('admin.kenaikan.proses'), [
        'tahun_asal_id' => $asal,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [
            $k['kelas7']->id => $k['kelas8']->id,
            $k['kelas8']->id => $kelas9b->id,
        ],
    ]);

    $response->assertRedirect(route('admin.kenaikan.index', ['tahun_asal_id' => $asal, 'tahun_tujuan_id' => $tujuan->id]));
    expect($s1->fresh()->kelas_id)->toBe($k['kelas8']->id)
        ->and($s1->fresh()->tahun_ajaran_id)->toBe($tujuan->id)
        ->and($s2->fresh()->kelas_id)->toBe($kelas9b->id)
        ->and(RiwayatKelas::where('status', 'aktif')->count())->toBe(2);
});

test('tingkat akhir diproses lulus dan akun dinonaktifkan', function () {
    $k = buatKelasNaik();
    $tujuan = buatTahunTujuan();
    $siswa = buatSiswaAktif(['kelas_id' => $k['kelas9']->id]);

    $this->actingAs(superAdmin())->post(route('admin.kenaikan.proses'), [
        'tahun_asal_id' => TahunAjaran::aktif()->first()->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$k['kelas9']->id => 'LULUS'],
    ])->assertSessionHas('success');

    $riwayat = RiwayatKelas::where('siswa_id', $siswa->id)->first();
    expect($riwayat)->not->toBeNull()
        ->and($riwayat->status)->toBe('lulus')
        ->and($riwayat->tahun_ajaran_id)->toBe($tujuan->id)
        ->and($siswa->fresh()->is_aktif)->toBeFalse();
});

test('override tinggal kelas menulis riwayat mengulang', function () {
    $k = buatKelasNaik();
    $tujuan = buatTahunTujuan();
    $siswa = buatSiswaAktif(['kelas_id' => $k['kelas7']->id]);

    $this->actingAs(superAdmin())->post(route('admin.kenaikan.proses'), [
        'tahun_asal_id' => TahunAjaran::aktif()->first()->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$k['kelas7']->id => $k['kelas8']->id],
        'override' => [$siswa->id => 'tinggal'],
    ])->assertSessionHas('success');

    $riwayat = RiwayatKelas::where('siswa_id', $siswa->id)->first();
    expect($riwayat->status)->toBe('mengulang')
        ->and($riwayat->kelas_id)->toBe($k['kelas7']->id)
        ->and($siswa->fresh()->is_aktif)->toBeTrue()
        ->and($siswa->fresh()->kelas_id)->toBe($k['kelas7']->id)
        ->and($siswa->fresh()->tahun_ajaran_id)->toBe($tujuan->id);
});

test('proses ganda tahun sama dilewati idempoten', function () {
    $k = buatKelasNaik();
    $tujuan = buatTahunTujuan();
    $siswa = buatSiswaAktif(['kelas_id' => $k['kelas7']->id]);

    $payload = [
        'tahun_asal_id' => TahunAjaran::aktif()->first()->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$k['kelas7']->id => $k['kelas8']->id],
    ];

    $this->actingAs(superAdmin())->post(route('admin.kenaikan.proses'), $payload)->assertSessionHas('success');
    $this->actingAs(superAdmin())->post(route('admin.kenaikan.proses'), $payload)->assertSessionHas('success');

    expect(RiwayatKelas::where('siswa_id', $siswa->id)->count())->toBe(1);
});

test('grup tanpa tujuan dilewati dan dilaporkan', function () {
    $k = buatKelasNaik();
    $tujuan = buatTahunTujuan();
    $siswa = buatSiswaAktif(['kelas_id' => $k['kelas7']->id]);

    $response = $this->actingAs(superAdmin())->post(route('admin.kenaikan.proses'), [
        'tahun_asal_id' => TahunAjaran::aktif()->first()->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$k['kelas7']->id => ''],
    ]);

    $response->assertSessionHas('success');
    expect(RiwayatKelas::where('siswa_id', $siswa->id)->count())->toBe(0)
        ->and(session('success'))->toContain('dilewati');
});

test('tahun asal sama dengan tujuan ditolak', function () {
    $k = buatKelasNaik();
    $asal = TahunAjaran::aktif()->first()->id;
    buatSiswaAktif(['kelas_id' => $k['kelas7']->id]);

    $this->actingAs(superAdmin())->post(route('admin.kenaikan.proses'), [
        'tahun_asal_id' => $asal,
        'tahun_tujuan_id' => $asal,
        'pemetaan' => [$k['kelas7']->id => $k['kelas8']->id],
    ])->assertSessionHasErrors('tahun_tujuan_id');
});

test('user tanpa permission execute ditolak memproses', function () {
    $k = buatKelasNaik();
    $tujuan = buatTahunTujuan();
    $user = User::factory()->create();
    $siswa = buatSiswaAktif(['kelas_id' => $k['kelas7']->id]);

    $this->actingAs($user)->post(route('admin.kenaikan.proses'), [
        'tahun_asal_id' => TahunAjaran::aktif()->first()->id,
        'tahun_tujuan_id' => $tujuan->id,
        'pemetaan' => [$k['kelas7']->id => $k['kelas8']->id],
    ])->assertForbidden();

    expect(RiwayatKelas::where('siswa_id', $siswa->id)->count())->toBe(0);
});
