<?php

/**
 * Dashboard admin.
 *
 * Fokusnya satu: angka di halaman harus berasal dari data nyata. Placeholder
 * yang lolos adalah kegagalan khas dashboard — kalau tes hanya memeriksa
 * "halaman render 200", template demo apa pun akan dianggap berhasil.
 */

use App\Models\Absensi;
use App\Models\CalonSiswa;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Menu;
use App\Models\PengumpulanTugas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    Menu::create(['name' => 'Dashboard', 'icon' => 'fa-solid fa-th-large', 'route' => 'admin.dashboard', 'order' => 1]);
});

/**
 * Siswa harus dibuat lewat User karena `siswas.user_id` NOT NULL.
 */
function siswaUji(?Rombel $rombel = null): Siswa
{
    static $n = 0;
    $n++;

    $rombel ??= Rombel::factory()->create();

    $user = User::factory()->create(['username' => 'siswa-dash-'.$n]);
    $user->assignRole('siswa');

    return Siswa::create([
        'user_id' => $user->id,
        'nis' => (string) (900000 + $n),
        'nisn' => '00900'.$n,
        'qr_token' => 'qr-dash-'.$n.'-'.Str::random(6),
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'kelas_id' => $rombel->kelas_id,
        'is_aktif' => true,
    ]);
}

test('jumlah siswa dan guru di kartu sama dengan isi database', function () {
    siswaUji();
    siswaUji();

    Guru::factory()->count(3)->create(['is_aktif' => true]);
    Guru::factory()->count(2)->create(['is_aktif' => false]);

    $respons = $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertOk();

    $respons->assertSee('2', false)
        ->assertSee('Siswa Aktif')
        ->assertSee('3', false)
        ->assertSee('Guru Aktif');
});

test('guru nonaktif tidak ikut dihitung', function () {
    Guru::factory()->count(1)->create(['is_aktif' => true]);
    Guru::factory()->count(4)->create(['is_aktif' => false]);

    $html = $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->getContent();

    // Kartu "Guru Aktif" menampilkan 1, bukan 5.
    expect($html)->toMatch('/Guru Aktif/');
    preg_match('/stat-value">(\d+)<\/div>\s*<div class="stat-label">Guru Aktif/', $html, $cocok);

    expect($cocok[1] ?? null)->toBe('1');
});

test('komposisi absensi hari ini dihitung dari tabel absensis', function () {
    $rombel = Rombel::factory()->create();

    foreach (['hadir', 'hadir', 'terlambat', 'sakit', 'alpa'] as $status) {
        Absensi::create([
            'rombel_id' => $rombel->id,
            'siswa_id' => siswaUji($rombel)->id,
            'tanggal' => Carbon::today()->toDateString(),
            'status' => $status,
        ]);
    }

    $respons = $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertOk();

    $respons->assertSee('5', false)
        ->assertSee('Absensi Tercatat Hari Ini')
        // 3 hadir: 2 hadir + 1 terlambat. Telat tetap masuk sekolah, jadi
        // menghitungnya sebagai hadir adalah angka yang jujur.
        ->assertSee('3 hadir')
        ->assertSee('1 alpa');
});

test('tren kehadiran menghitung terlambat sebagai hadir', function () {
    $rombel = Rombel::factory()->create();

    // Empat catatan: 1 hadir, 1 terlambat, 1 sakit, 1 alpa.
    // Kalau terlambat ikut dihitung hadir, persentasenya 50 — bukan 25.
    foreach (['hadir', 'terlambat', 'sakit', 'alpa'] as $status) {
        Absensi::create([
            'rombel_id' => $rombel->id,
            'siswa_id' => siswaUji($rombel)->id,
            'tanggal' => Carbon::today()->subDay()->toDateString(),
            'status' => $status,
        ]);
    }

    $html = $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->getContent();

    // Ambil JSON konfigurasi chart tren, bukan markup bebas: assertion atas
    // string "%</div>" bisa kepilih angka kartu lain dan tetap hijau.
    preg_match('/<script type="application\/json" data-chart data-target="chartTrenKehadiran">(.*?)<\/script>/s', $html, $cocok);

    expect($cocok[1] ?? null)->not->toBeNull();

    $konfigurasi = json_decode($cocok[1], true);
    $labels = $konfigurasi['data']['labels'];
    $data = $konfigurasi['data']['datasets'][0]['data'];

    // Rentang 30 hari dihitung dari hari ini mundur 29 hari, lalu maju 30
    // kali. Jadi indeks terakhir (29) adalah HARI INI, dan kemarin ada di
    // indeks 28. Keduanya harus dicek supaya keselarasan tanggal ikut terkunci.
    expect(count($data))->toBe(30)
        ->and($data[28])->toBe(50)
        ->and($data[29])->toBe(0)
        ->and($labels[28])->toBe(Carbon::today()->subDay()->format('j M'));
});

test('absensi dari hari lain tidak ikut terhitung sebagai hari ini', function () {
    $rombel = Rombel::factory()->create();

    Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => siswaUji($rombel)->id,
        'tanggal' => Carbon::today()->subDays(10)->toDateString(),
        'status' => 'hadir',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Belum ada absensi tercatat hari ini.');
});

test('jumlah pengumpulan yang belum dinilai cocok dengan antrean', function () {
    $rombel = Rombel::factory()->create();
    $mapel = MataPelajaran::factory()->create();
    $guru = Guru::factory()->create();

    $tugas = Tugas::create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'guru_id' => $guru->id,
        'judul' => 'Tugas Antrean',
        'is_aktif' => true,
    ]);

    foreach (range(1, 3) as $i) {
        PengumpulanTugas::create([
            'tugas_id' => $tugas->id,
            'siswa_id' => siswaUji($rombel)->id,
            'nilai' => null,
        ]);
    }

    foreach (range(1, 2) as $i) {
        PengumpulanTugas::create([
            'tugas_id' => $tugas->id,
            'siswa_id' => siswaUji($rombel)->id,
            'nilai' => 80,
        ]);
    }

    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('3 pengumpulan belum dinilai')
        ->assertSee('Tugas Antrean');
});

test('status PPDB dihitung per status pendaftaran', function () {
    static $n = 0;

    foreach ([['menunggu', 4], ['diterima', 2]] as [$status, $jumlah]) {
        foreach (range(1, $jumlah) as $i) {
            $n++;
            $user = User::factory()->create();

            CalonSiswa::create([
                'user_id' => $user->id,
                'no_pendaftaran' => 'DASH-'.$n,
                'nik' => (string) (3200000000000000 + $n),
                'nama_lengkap' => 'Pendaftar '.$n,
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2012-01-01',
                'agama' => 'Islam',
                'alamat' => 'Jl. Uji',
                'tahun_ajaran_id' => TahunAjaran::factory()->create()->id,
                'status_pendaftaran' => $status,
            ]);
        }
    }

    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('6 pendaftar')
        ->assertSee('Diterima')
        ->assertSee('Daftar Ulang');
});

test('halaman tidak lagi menampilkan sisa template demo', function () {
    // Placeholder lama menampilkan Revenue, pesanan, dan nama orang dari
    // template pihak ketiga. Kalau salah satu muncul lagi, berarti view belum
    // benar-benar diganti.
    $respons = $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertOk();

    foreach ([
        'Total Revenue',
        'Total Orders',
        'Conversion Rate',
        'Traffic Sources',
        'Sarah Johnson',
        'Marcus Lee',
        'Priya Patel',
        'Nexus Pro Bundle',
        'Latest 6 transactions',
    ] as $jejak) {
        $respons->assertDontSee($jejak);
    }
});

test('angka tetap terbaca tanpa javascript karena ada padanan teks', function () {
    $rombel = Rombel::factory()->create();

    Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => siswaUji($rombel)->id,
        'tanggal' => Carbon::today()->toDateString(),
        'status' => 'hadir',
    ]);

    $html = $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->getContent();

    // Konfigurasi chart ada di markup sebagai JSON, dan angka aslinya juga
    // ditulis sebagai teks — bukan cuma digambar ke canvas.
    expect($html)->toContain('data-chart')
        ->and($html)->toContain('chartAbsensiHariIni')
        ->and($html)->toContain('Absensi Tercatat Hari Ini')
        ->and($html)->toContain('hadir');
});

test('beranda admin merender dashboard tanpa rantai redirect', function () {
    // Redirect ke /admin/dashboard hanya menambah satu langkah yang bisa gagal
    // tanpa menambah apa pun.
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertOk()
        ->assertSee('Siswa Aktif');
});

test('tamu tidak dapat membuka dashboard admin', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});
