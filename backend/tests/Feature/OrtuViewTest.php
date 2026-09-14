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
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
});

if (! function_exists('siswaOrtuUji')) {
    function siswaOrtuUji(string $nis, string $nama, ?Rombel $rombel = null): Siswa
    {
        static $n = 0;
        $n++;

        $rombel ??= Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)->firstOrFail();

        $user = User::factory()->create(['username' => 'siswa-ortu-'.$n, 'name' => $nama]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'nisn' => sprintf('008050%04d', $n),
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

test('dashboard menampilkan wali dan persentase bulanan', function () {
    $rombel = Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
        ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)->firstOrFail();
    $guru = Guru::where('nama', 'Guru A')->first();
    $rombel->update(['wali_guru_id' => $guru->id]);

    $siswa = siswaOrtuUji('8001', 'Anak Ortu Satu', $rombel);
    $ortu = User::where('username', 'ortu-'.$siswa->nisn)->firstOrFail();

    Absensi::create(['rombel_id' => $rombel->id, 'siswa_id' => $siswa->id, 'tanggal' => now()->format('Y-m-01'), 'status' => 'hadir', 'metode' => 'manual']);
    Absensi::create(['rombel_id' => $rombel->id, 'siswa_id' => $siswa->id, 'tanggal' => now()->format('Y-m-02'), 'status' => 'alpa', 'metode' => 'manual']);

    $this->actingAs($ortu)->get(route('ortu.dashboard'))
        ->assertOk()->assertSee('Guru A')->assertSee('50%');
});

test('detail mendukung filter semester ganjil', function () {
    $tahun = TahunAjaran::aktif()->first();
    $rombel = Rombel::where('tahun_ajaran_id', $tahun->id)->firstOrFail();
    $siswa = siswaOrtuUji('8001', 'Anak Ortu Satu', $rombel);
    $ortu = User::where('username', 'ortu-'.$siswa->nisn)->firstOrFail();
    $tautan = WaliMurid::where('user_id', $ortu->id)->firstOrFail();

    Absensi::create(['rombel_id' => $rombel->id, 'siswa_id' => $siswa->id, 'tanggal' => substr($tahun->tanggal_mulai, 0, 4).'-08-10', 'status' => 'hadir', 'metode' => 'manual']);
    Absensi::create(['rombel_id' => $rombel->id, 'siswa_id' => $siswa->id, 'tanggal' => substr($tahun->tanggal_selesai, 0, 4).'-02-10', 'status' => 'izin', 'metode' => 'manual']);

    $this->actingAs($ortu)->get(route('ortu.anak', ['waliMurid' => $tautan->id, 'periode' => 'ganjil', 'tahun_ajaran_id' => $tahun->id]))
        ->assertOk()->assertSee('Hadir: 1')->assertDontSee('Izin: 1');

    $this->actingAs($ortu)->get(route('ortu.anak', ['waliMurid' => $tautan->id, 'periode' => 'genap', 'tahun_ajaran_id' => $tahun->id]))
        ->assertOk()->assertSee('Izin: 1');
});

test('ortu dapat ganti password dengan current_password', function () {
    $siswa = siswaOrtuUji('8001', 'Anak Ortu Satu');
    $ortu = User::where('username', 'ortu-'.$siswa->nisn)->firstOrFail();

    $response = $this->actingAs($ortu)->put(route('ortu.profil.update'), [
        'password_saat_ini' => $siswa->nisn,
        'password' => 'barupassword',
        'password_confirmation' => 'barupassword',
    ]);

    $response->assertRedirect(route('ortu.profil'));
    expect(Hash::check('barupassword', $ortu->fresh()->password))->toBeTrue();
});

test('ganti password tanpa current_password ditolak', function () {
    $siswa = siswaOrtuUji('8001', 'Anak Ortu Satu');
    $ortu = User::where('username', 'ortu-'.$siswa->nisn)->firstOrFail();

    $this->actingAs($ortu)->put(route('ortu.profil.update'), [
        'password' => 'barupassword',
        'password_confirmation' => 'barupassword',
    ])->assertSessionHasErrors('password_saat_ini');
});
