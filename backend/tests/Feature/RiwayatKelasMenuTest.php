<?php

use App\Models\Kelas;
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

/*
 * Menu Riwayat Kelas.
 *
 * Test ini sengaja merender kedua halaman. Sebelumnya tidak ada satu pun test
 * yang memanggil `GET riwayat-kelas.index` (selain cek 403 untuk guru) maupun
 * `GET riwayat-kelas.show`, dan dua bug lolos ke develop tanpa terdeteksi:
 *
 *   - `index` menulis `$siswa->tahunAjaran?->nama`, padahal kolomnya
 *     `nama_tahun_ajaran`, sehingga kolomnya selalu "—".
 *   - `show` menulis `{{ $t->nama }}` di dropdown tahun ajaran, sehingga
 *     opsi yang tampil kosong semua. Admin tidak bisa tahu tahun mana yang
 *     sedang diedit.
 *
 * Both are "cosmetic" — tidak ada error, tidak ada 500 — dan justru itu yang
 * membuatnya lolos. Assertion di bawah.Assertagainst the rendered text, not
 * against the controller's return value, supaya kelas yang benar-benar tampil
 * ke admin ikut diperiksa.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
});

if (! function_exists('adminRiwayatKelas')) {
    function adminRiwayatKelas(): User
    {
        $user = User::factory()->create(['username' => 'admin-riwayat-'.uniqid()]);
        $user->assignRole('admin');

        return $user;
    }
}

if (! function_exists('rombelRiwayatKelas')) {
    function rombelRiwayatKelas(string $nama): Rombel
    {
        return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', $nama))->firstOrFail();
    }
}

/**
 * Siswa dengan satu baris riwayat `aktif` — keadaan normal.
 */
/**
 * Nama di factory sengaja tidak dibiarkan dari Faker.
 *
 * Blade meng-escape `{{ }}`, jadi nama berkoma seperti "O'Connell" muncul di
 * HTML sebagai `O&#039;Connell`. Selain itu sekitar 1,6% nama Faker memuat
 * karakter yang berubah saat di-escape, sehingga `toContain($nama)` gagal
 * sesekali tanpa ada perubahan kode.
 */
if (! function_exists('siswaSehat')) {
    function siswaRiwayatSehat(string $kode): Siswa
    {
        $rombel = rombelRiwayatKelas('7A');

        $user = User::factory()->create([
            'username' => "siswa-sehat-{$kode}",
            'name' => "Siswa Sehat {$kode}",
        ]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => '5'.$kode,
            'nisn' => '0099'.str_pad($kode, 6, '0', STR_PAD_LEFT),
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

/**
 * Siswa tanpa baris `aktif` sama sekali — lenyap dari rekap.
 */
if (! function_exists('siswaRiwayatTanpaAktif')) {
    function siswaRiwayatTanpaAktif(string $kode): Siswa
    {
        $rombel = rombelRiwayatKelas('7A');

        $user = User::factory()->create([
            'username' => "siswa-tanpa-{$kode}",
            'name' => "Siswa Tanpa Aktif {$kode}",
        ]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => '6'.$kode,
            'nisn' => '0098'.str_pad($kode, 6, '0', STR_PAD_LEFT),
            'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
            'kelas_id' => $rombel->kelas_id,
            'is_aktif' => true,
        ]);

        RiwayatKelas::create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $rombel->kelas_id,
            'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
            'status' => 'pindah',
        ]);

        return $siswa;
    }
}

/*
 * Bug label: dua halaman ini menampilkan kolom yang tidak ada.
 */

test('daftar menampilkan nama tahun ajaran, bukan tanda hubung', function () {
    $siswa = siswaRiwayatSehat('01');

    $html = $this->actingAs(adminRiwayatKelas())
        ->get(route('admin.riwayat-kelas.index'))
        ->assertOk()
        ->getContent();

    $tahun = TahunAjaran::aktif()->firstOrFail();

    // Kalau kolomnya salah (`nama`), teks ini tidak akan pernah muncul.
    expect($html)->toContain($tahun->nama_tahun_ajaran)
        ->and($html)->toContain($siswa->user->name);
});

test('dropdown tahun ajaran pada halaman koreksi menampilkan label', function () {
    $siswa = siswaRiwayatSehat('02');

    $html = $this->actingAs(adminRiwayatKelas())
        ->get(route('admin.riwayat-kelas.show', $siswa))
        ->assertOk()
        ->getContent();

    // Semua tahun ajaran harus muncul sebagai teks di dalam <option>.
    // Sebelumnya `{{ $t->nama }}` membuat semuanya kosong.
    foreach (TahunAjaran::pluck('nama_tahun_ajaran') as $nama) {
        expect($html)->toContain($nama);
    }
});

/*
 * Filter anomali.
 */

test('daftar menandai siswa bermasalah dan yang sehat', function () {
    $sehat = siswaRiwayatSehat('03');
    $tanpa = siswaRiwayatTanpaAktif('03');

    $html = $this->actingAs(adminRiwayatKelas())
        ->get(route('admin.riwayat-kelas.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('Sehat')
        ->and($html)->toContain('Tanpa baris aktif');

    // Kedua siswa ada di daftar, tapi hanya yang bermasalah yang membawa badge.
    $barisSehat = barisHtml($html, $sehat->user->name);
    $barisTanpa = barisHtml($html, $tanpa->user->name);

    expect($barisSehat)->toContain('Sehat')
        ->and($barisSehat)->not->toContain('Tanpa baris aktif')
        ->and($barisTanpa)->toContain('Tanpa baris aktif');
});

test('filter tanpa aktif hanya menampilkan siswa tanpa baris aktif', function () {
    $sehat = siswaRiwayatSehat('04');
    $tanpa = siswaRiwayatTanpaAktif('04');

    $html = $this->actingAs(adminRiwayatKelas())
        ->get(route('admin.riwayat-kelas.index', ['filter' => 'tanpa-aktif']))
        ->assertOk()
        ->getContent();

    expect($html)->toContain($tanpa->user->name)
        ->and($html)->not->toContain($sehat->user->name);
});

test('filter pointer tidak sinkron menemukan kelas yang berbeda dari riwayat', function () {
    $siswa = siswaRiwayatSehat('05');
    $rombelLain = rombelRiwayatKelas('7B');

    // Tundayangkan pointer siswa ke kelas yang tidak ditunjuk baris `aktif`.
    $siswa->update(['kelas_id' => $rombelLain->kelas_id]);

    $html = $this->actingAs(adminRiwayatKelas())
        ->get(route('admin.riwayat-kelas.index', ['filter' => 'drift']))
        ->assertOk()
        ->getContent();

    expect($html)->toContain($siswa->user->name)
        ->and($html)->toContain('Pointer tidak sinkron');
});

test('siswa sehat tidak muncul di filter drift', function () {
    $sehat = siswaRiwayatSehat('06');
    siswaRiwayatTanpaAktif('06');

    $html = $this->actingAs(adminRiwayatKelas())
        ->get(route('admin.riwayat-kelas.index', ['filter' => 'drift']))
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain($sehat->user->name);
});

test('filter dua baris aktif menemukan siswa dengan dobel baris aktif', function () {
    $siswa = siswaRiwayatSehat('07');
    $rombelLain = rombelRiwayatKelas('7B');

    // Constraint (siswa, kelas, tahun) masih melebihi DATABASE, jadi dobel baris
    // harus beda kelas atau beda tahun.
    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $rombelLain->kelas_id,
        'tahun_ajaran_id' => $rombelLain->tahun_ajaran_id,
        'status' => 'aktif',
    ]);

    $html = $this->actingAs(adminRiwayatKelas())
        ->get(route('admin.riwayat-kelas.index', ['filter' => 'ganda-aktif']))
        ->assertOk()
        ->getContent();

    expect($html)->toContain($siswa->user->name)
        ->and($html)->toContain('Dua baris aktif');
});

test('filter tidak dikenal jatuh ke semua siswa', function () {
    $sehat = siswaRiwayatSehat('08');

    $html = $this->actingAs(adminRiwayatKelas())
        ->get(route('admin.riwayat-kelas.index', ['filter' => 'ngawur']))
        ->assertOk()
        ->getContent();

    expect($html)->toContain($sehat->user->name);
});

test('pencarian bisa memakai NIS dan NISN', function () {
    $siswa = siswaRiwayatSehat('09');
    siswaRiwayatTanpaAktif('09');

    // Dulu hanya nama yang dicari. NIS/NISN justru yang dipakai saat mencari
    // siswa yang salah catat.
    $byNis = $this->actingAs(adminRiwayatKelas())
        ->get(route('admin.riwayat-kelas.index', ['search' => $siswa->nis]))
        ->assertOk()->getContent();

    expect($byNis)->toContain($siswa->user->name);

    $byNisn = $this->actingAs(adminRiwayatKelas())
        ->get(route('admin.riwayat-kelas.index', ['search' => $siswa->nisn]))
        ->assertOk()->getContent();

    expect($byNisn)->toContain($siswa->user->name);
});

/*
 * Tambah baris riwayat.
 */

test('halaman koreksi menawarkan form tambah hanya saat tidak ada baris aktif', function () {
    $sehat = siswaRiwayatSehat('10');
    $tanpa = siswaRiwayatTanpaAktif('10');

    $htmlSehat = $this->actingAs(adminRiwayatKelas())
        ->get(route('admin.riwayat-kelas.show', $sehat))->assertOk()->getContent();
    $htmlTanpa = $this->actingAs(adminRiwayatKelas())
        ->get(route('admin.riwayat-kelas.show', $tanpa))->assertOk()->getContent();

    expect($htmlSehat)->not->toContain('Tambah Baris Riwayat')
        ->and($htmlTanpa)->toContain('Tambah Baris Riwayat');
});

test('menambah baris aktif menyelaraskan pointer siswa', function () {
    $tanpa = siswaRiwayatTanpaAktif('11');
    $rombel = rombelRiwayatKelas('7C');

    // Ini inti dari formnya: kalau pointer tidak ikut diselaraskan, baris
    // `aktif` yang baru dibuat langsung menjadi anomali "pointer tidak sinkron"
    // yang muncul di filter — masalah dibuat oleh form yang dimaksud memperbaikinya.
    $this->actingAs(adminRiwayatKelas())
        ->post(route('admin.riwayat-kelas.store', $tanpa), [
            'kelas_id' => $rombel->kelas_id,
            'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
            'status' => 'aktif',
        ])->assertRedirect(route('admin.riwayat-kelas.show', $tanpa));

    expect($tanpa->fresh()->kelas_id)->toBe($rombel->kelas_id)
        ->and($tanpa->fresh()->tahun_ajaran_id)->toBe($rombel->tahun_ajaran_id)
        ->and($tanpa->riwayatKelas()->where('status', 'aktif')->count())->toBe(1);

    // Setelahnya siswa tidak boleh muncul di filter drift.
    $html = $this->actingAs(adminRiwayatKelas())
        ->get(route('admin.riwayat-kelas.index', ['filter' => 'drift']))
        ->assertOk()->getContent();

    expect($html)->not->toContain($tanpa->user->name);
});

test('menambah baris historis tidak memindahkan pointer siswa', function () {
    $tanpa = siswaRiwayatTanpaAktif('12');
    $kelasAsal = $tanpa->kelas_id;
    $tahunAsal = $tanpa->tahun_ajaran_id;
    $rombel = rombelRiwayatKelas('7C');

    $this->actingAs(adminRiwayatKelas())
        ->post(route('admin.riwayat-kelas.store', $tanpa), [
            'kelas_id' => $rombel->kelas_id,
            'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
            'status' => 'mengulang',
        ])->assertRedirect();

    // Status selain `aktif` hanya menambah catatan; siswa tidak boleh berpindah.
    expect($tanpa->fresh()->kelas_id)->toBe($kelasAsal)
        ->and($tanpa->fresh()->tahun_ajaran_id)->toBe($tahunAsal)
        ->and($tanpa->riwayatKelas()->where('status', 'aktif')->count())->toBe(0);
});

test('menambah baris ditolak bila bentrok dengan pasangan yang sudah ada', function () {
    $siswa = siswaRiwayatSehat('13');

    $this->actingAs(adminRiwayatKelas())
        ->post(route('admin.riwayat-kelas.store', $siswa), [
            'kelas_id' => $siswa->kelas_id,
            'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
            'status' => 'pindah',
        ])->assertSessionHasErrors('kelas_id');

    expect($siswa->riwayatKelas()->count())->toBe(1);
});

test('menambah baris melepas baris aktif lain di tahun yang sama', function () {
    $siswa = siswaRiwayatSehat('14');
    $rombelLain = rombelRiwayatKelas('7B');

    $this->actingAs(adminRiwayatKelas())
        ->post(route('admin.riwayat-kelas.store', $siswa), [
            'kelas_id' => $rombelLain->kelas_id,
            'tahun_ajaran_id' => $rombelLain->tahun_ajaran_id,
            'status' => 'aktif',
        ])->assertRedirect();

    // Tidak boleh jadi anggota dua rombel sekaligus.
    expect($siswa->riwayatKelas()->where('status', 'aktif')->count())->toBe(1)
        ->and($siswa->fresh()->kelas_id)->toBe($rombelLain->kelas_id);
});

test('menambah baris butuh permission khusus', function () {
    $guru = User::factory()->create(['username' => 'guru-riwayat-'.uniqid()]);
    $guru->assignRole('guru');
    $tanpa = siswaRiwayatTanpaAktif('15');

    $this->actingAs($guru)
        ->post(route('admin.riwayat-kelas.store', $tanpa), [
            'kelas_id' => $tanpa->kelas_id,
            'tahun_ajaran_id' => $tanpa->tahun_ajaran_id,
            'status' => 'aktif',
        ])->assertForbidden();

    expect($tanpa->riwayatKelas()->where('status', 'aktif')->count())->toBe(0);
});

/**
 * Potong satu baris <tr> berdasarkan nama siswa, supaya assertion badge bisa
 * menempel pada baris yang tepat dan bukan pada teks mana saja di halaman.
 */
function barisHtml(string $html, string $nama): string
{
    if (preg_match_all('/<tr>.*?<\/tr>/s', $html, $m)) {
        foreach ($m[0] as $tr) {
            if (str_contains($tr, $nama)) {
                return $tr;
            }
        }
    }

    return '';
}
