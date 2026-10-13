<?php

/**
 * Dashboard guru.
 *
 * Yang paling penting di sini bukan angkanya, tapi cakupan. Guru tanpa
 * penugasan harus melihat NOL, bukan seluruh sekolah — kalau tidak, dashboard
 * ini jadi halaman kebocoran data antar guru yang paling mudah dibaca.
 */

use App\Models\Absensi;
use App\Models\Exam;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\PengumpulanTugas;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Models\User;
use App\Support\RuteBeranda;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(MenuSeeder::class);
});

/**
 * Guru dengan baris `gurus`, tanpa penugasan apa pun.
 */
function guruTanpaKelas(): User
{
    $user = User::factory()->create();
    $user->assignRole('guru');
    Guru::create([
        'user_id' => $user->id,
        'nama' => $user->name,
        'jenis_kelamin' => 'L',
        'is_aktif' => true,
    ]);

    return $user;
}

/**
 * Guru yang mengampu mapel di rombel, atau wali rombel itu.
 */
function guruDenganKelas(Rombel $rombel, ?MataPelajaran $mapel = null, bool $sebagaiWali = false): User
{
    $user = guruTanpaKelas();
    $guru = Guru::where('user_id', $user->id)->firstOrFail();

    if ($sebagaiWali) {
        $rombel->update(['wali_guru_id' => $guru->id]);
    }

    if ($mapel !== null) {
        Pengampu::create([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'rombel_id' => $rombel->id,
        ]);
    }

    return $user;
}

/**
 * Rombel dengan nama kelas yang bisa diasosiasikan, supaya test bisa
 * memeriksa kelas mana yang muncul dan mana yang tidak.
 */
function rombelUjiGuru(string $namaKelas): Rombel
{
    $kelas = Kelas::factory()->create(['nama_kelas' => $namaKelas]);

    return Rombel::factory()->create(['kelas_id' => $kelas->id, 'wali_guru_id' => null]);
}

test('guru tanpa penugasan melihat nol kelas, bukan seluruh kelas', function () {
    // Rombel milik orang lain sengaja dibuat lebih dulu. Kalau cakupan bocor,
    // kelas ini akan muncul di dashboard guru yang tidak punya penugasan.
    $rombel = rombelUjiGuru('7A');
    guruDenganKelas($rombel, null, true);

    $guruKosong = guruTanpaKelas();

    $respons = $this->actingAs($guruKosong)
        ->get(route('guru.dashboard'))
        ->assertOk();

    $respons->assertSee('Belum ada kelas yang diampu')
        ->assertDontSee('7A');
});

test('guru tanpa penugasan tidak melihat kelas milik guru lain', function () {
    $rombelLain = rombelUjiGuru('7A');
    $guruPemilik = guruDenganKelas($rombelLain, null, true);

    $guruKosong = guruTanpaKelas();

    $this->actingAs($guruKosong)
        ->get(route('guru.dashboard'))
        ->assertOk()
        ->assertDontSee('7A');
});

test('guru melihat kelas yang ia ampu', function () {
    $rombel = rombelUjiGuru('7A');
    $mapel = MataPelajaran::factory()->create();
    $guru = guruDenganKelas($rombel, $mapel);

    $this->actingAs($guru)
        ->get(route('guru.dashboard'))
        ->assertOk()
        ->assertSee('7A')
        ->assertSee('Kelas Diampu')
        ->assertSee('Dashboard Guru');
});

test('guru wali kelas melihat kelasnya tanpa harus mengampu mapel', function () {
    $rombel = rombelUjiGuru('7B');
    $guru = guruDenganKelas($rombel, null, true);

    $this->actingAs($guru)
        ->get(route('guru.dashboard'))
        ->assertOk()
        ->assertSee('7B');
});

test('persentase absensi dihitung hanya dari kelas sendiri', function () {
    $rombelSaya = rombelUjiGuru('7A');
    $rombelOrang = rombelUjiGuru('8B');
    guruDenganKelas($rombelOrang, null, true);

    $guru = guruDenganKelas($rombelSaya, null, true);
    $hari = Carbon::today();

    // Di kelas sendiri: 1 dari 2 siswa hadir.
    foreach (range(1, 2) as $i) {
        $siswa = siswaRombelUji($rombelSaya, '9'.$i);
        Absensi::create([
            'rombel_id' => $rombelSaya->id,
            'siswa_id' => $siswa->id,
            'tanggal' => $hari->toDateString(),
            'status' => $i === 1 ? 'hadir' : 'alpa',
        ]);
    }

    // Di kelas orang lain: semua hadir. Kalau cakupan bocor, angkanya jadi 100%.
    foreach (range(1, 3) as $i) {
        $siswa = siswaRombelUji($rombelOrang, '8'.$i);
        Absensi::create([
            'rombel_id' => $rombelOrang->id,
            'siswa_id' => $siswa->id,
            'tanggal' => $hari->toDateString(),
            'status' => 'hadir',
        ]);
    }

    $this->actingAs($guru)
        ->get(route('guru.dashboard'))
        ->assertOk()
        ->assertSee('7A')
        ->assertDontSee('8B')
        ->assertSee('50%');
});

test('kelas tanpa anggota menampilkan belum diisi, bukan nol persen', function () {
    $rombel = rombelUjiGuru('7C');
    $guru = guruDenganKelas($rombel, null, true);

    // Nol anggota harus tampil "Belum diisi". Menampilkan "0%" untuk kelas yang
    // belum diisi absensinya terbaca sebagai kegagalan guru.
    $this->actingAs($guru)
        ->get(route('guru.dashboard'))
        ->assertOk()
        ->assertSee('Belum diisi')
        ->assertDontSee('0%');
});

test('guru mengarahkan ke dashboardnya sendiri dari admin root', function () {
    $rombel = rombelUjiGuru('7A');
    $guru = guruDenganKelas($rombel, null, true);

    $this->actingAs($guru)
        ->get('/admin')
        ->assertRedirect(route('guru.dashboard'));
});

test('admin tetap melihat laporan sekolah meski memegang role guru', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect(RuteBeranda::untuk($admin))->toBe('admin.dashboard');
});

test('guru piket juga mendapat dashboard sendiri', function () {
    $user = User::factory()->create();
    $user->assignRole('guru-piket');
    Guru::create([
        'user_id' => $user->id, 'nama' => $user->name,
        'jenis_kelamin' => 'P', 'is_aktif' => true,
    ]);

    expect(RuteBeranda::untuk($user))->toBe('guru.dashboard');
});

test('siswa dan orang tua tidak bisa membuka dashboard guru', function () {
    $siswa = User::factory()->create();
    $siswa->assignRole('siswa');

    $ortu = User::factory()->create();
    $ortu->assignRole('orang-tua');

    $this->actingAs($siswa)->get(route('guru.dashboard'))->assertForbidden();
    $this->actingAs($ortu)->get(route('guru.dashboard'))->assertForbidden();
});

test('admin tidak bisa membuka dashboard guru karena bukan area Privileged', function () {
    // Route group dibatasi role guru. Admin sengaja TIDAK diberi akses ke sini
    // supaya "dashboard guru" benar-benar milik guru; admin punya laporan
    // sekolahnya sendiri di /admin.
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get(route('guru.dashboard'))->assertForbidden();
});

test('menu dashboard hanya muncul untuk role yang berhak', function () {
    $guru = guruDenganKelas(rombelUjiGuru('7A'), null, true);

    $namaGuru = $this->actingAs($guru)->get(route('guru.dashboard'))->assertOk()->getContent();
    expect($namaGuru)->toContain('Dashboard Guru')
        ->and($namaGuru)->not->toContain('>Dashboard<');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $htmlAdmin = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();
    expect($htmlAdmin)->toContain('>Dashboard<')
        ->and($htmlAdmin)->not->toContain('Dashboard Guru');
});

test('brand sidebar guru mengarah ke dashboard guru', function () {
    $guru = guruDenganKelas(rombelUjiGuru('7A'), null, true);

    $html = $this->actingAs($guru)->get(route('guru.dashboard'))->assertOk()->getContent();

    expect($html)->toContain('href="'.route('guru.dashboard').'"');
});

test('pengumpulan di luar kelas sendiri tidak masuk antrean penilaian', function () {
    $rombelSaya = rombelUjiGuru('7A');
    $rombelOrang = rombelUjiGuru('8B');

    $guru = guruDenganKelas($rombelSaya, MataPelajaran::factory()->create());
    $mapelId = Pengampu::where('guru_id', Guru::where('user_id', $guru->id)->value('id'))->value('mata_pelajaran_id');

    $tugasAsing = Tugas::create([
        'rombel_id' => $rombelOrang->id,
        'mata_pelajaran_id' => $mapelId,
        'judul' => 'Tugas Kelas Orang Lain',
        'is_aktif' => true,
    ]);

    PengumpulanTugas::create([
        'tugas_id' => $tugasAsing->id,
        'siswa_id' => siswaRombelUji($rombelOrang, '77')->id,
        'nilai' => null,
    ]);

    $this->actingAs($guru)
        ->get(route('guru.dashboard'))
        ->assertOk()
        ->assertDontSee('Tugas Kelas Orang Lain');
});

test('ujian published di kelas sendiri tampil, yang di kelas orang lain tidak', function () {
    $rombelSaya = rombelUjiGuru('7A');
    $rombelOrang = rombelUjiGuru('8B');

    $guru = guruDenganKelas($rombelSaya, MataPelajaran::factory()->create());
    $guruId = Guru::where('user_id', $guru->id)->value('id');
    $mapelId = Pengampu::where('guru_id', $guruId)->value('mata_pelajaran_id');

    $pengampu = User::factory()->create();
    $pengampu->assignRole('admin');

    foreach (['Ujian Kelas Sendiri' => $rombelSaya->id, 'Ujian Kelas Orang Lain' => $rombelOrang->id] as $nama => $rombelId) {
        Exam::create([
            'rombel_id' => $rombelId,
            'mata_pelajaran_id' => $mapelId,
            'name' => $nama,
            'duration_minutes' => 60,
            'max_violation_count' => 3,
            'status' => 'published',
            'created_by' => $pengampu->id,
        ]);
    }

    $this->actingAs($guru)
        ->get(route('guru.dashboard'))
        ->assertOk()
        ->assertSee('Ujian Kelas Sendiri')
        ->assertDontSee('Ujian Kelas Orang Lain');
});

test('wali kelas tidak melihat antrean penilaian mapel yang bukan diampunya', function () {
    // Guru adalah wali 7A, tapi hanya mengampu Matematika di 8B. Tugas Fisika
    // di 7A milik guru lain: secara rombel dia berhak masuk, secara mapel tidak.
    // Kalau hanya cakupan rombel yang dipakai, antrean ini ikut terbuka.
    $rombel = rombelUjiGuru('7A');
    $mapelDiaampu = MataPelajaran::factory()->create();
    $mapelOrangLain = MataPelajaran::factory()->create();

    $guru = guruDenganKelas($rombel, $mapelDiaampu, true);

    // Buat supaya dia mengampu mapel dia di rombel LAIN, supaya $mapelIds
    // tidak kosong (kalau kosong blok ini early-return dan test jadi hampa).
    $rombelLain = rombelUjiGuru('8B');
    Pengampu::create([
        'guru_id' => Guru::where('user_id', $guru->id)->value('id'),
        'mata_pelajaran_id' => $mapelDiaampu->id,
        'rombel_id' => $rombelLain->id,
    ]);

    $tugasFisika = Tugas::create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapelOrangLain->id,
        'judul' => 'Tugas Fisika Kelas Tetangga',
        'is_aktif' => true,
    ]);

    PengumpulanTugas::create([
        'tugas_id' => $tugasFisika->id,
        'siswa_id' => siswaRombelUji($rombel, '66')->id,
        'nilai' => null,
    ]);

    $this->actingAs($guru)
        ->get(route('guru.dashboard'))
        ->assertOk()
        ->assertDontSee('Tugas Fisika Kelas Tetangga');
});

function siswaRombelUji(Rombel $rombel, string $nis): Siswa
{
    static $n = 0;
    $n++;

    $user = User::factory()->create(['username' => 'siswa-guru-'.$n]);
    $user->assignRole('siswa');

    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nis' => $nis,
        'nisn' => '0088'.$nis,
        'qr_token' => 'qr-'.$nis.'-'.Str::random(6),
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'kelas_id' => $rombel->kelas_id,
        'is_aktif' => true,
    ]);

    // `Rombel::anggotaIds*()` membaca dari `riwayat_kelas`, bukan dari
    // `siswas`. Tanpa baris ini rombel selalu terlihat tanpa anggota dan
    // persentase kehadiran tidak pernah terhitung.
    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $rombel->kelas_id,
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'status' => 'aktif',
    ]);

    return $siswa;
}
