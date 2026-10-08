<?php

use App\Actions\Rombel\SalinRombelAction;
use App\Actions\Siswa\ProsesKenaikanAction;
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
 * Kenaikan kelas lintas tahun ajaran dan filter "Pointer tidak sinkron".
 *
 * Status `aktif` pada baris riwayat dari tahun yang sudah lewat itu BENAR,
 * bukan sisa yang perlu dibersihkan. Siswa memang aktif di kelas itu sepanjang
 * tahun itu, dan `pindah` hanya dipakai untuk perpindahan kelas DALAM satu
 * tahun ajaran. Yang harus benar adalah filter yang membandingkan pointer
 * siswa dengan baris riwayat: kalau tidak dikorelasikan dengan tahun, setiap
 * siswa yang pernah naik kelas terbaca sebagai "pointer tidak sinkron".
 *
 * Test di sini memakai `ProsesKenaikanAction` dan `SalinRombelAction` sungguhan
 * — jalur yang sama persis dengan tombol "Jalankan" di menu Tahun Ajaran Baru —
 * bukan baris riwayat yang dirakit manual. Fixture sebelumnya hanya menguji kelas
 * 7A-7D di satu tahun yang sama, sehingga kasus lintas tahun tidak pernah
 * tersentuh.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
});

if (! function_exists('adminKenaikan')) {
    function adminKenaikan(): User
    {
        $user = User::factory()->create(['username' => 'admin-kenaikan-'.uniqid()]);
        $user->assignRole('admin');

        return $user;
    }
}

/**
 * Tahun ajaran tujuan yang belum aktif.
 *
 * `firstOrCreate`, bukan `create`: helper ini dipanggil lebih dari sekali dalam
 * satu test (sekali untuk menjalankan kenaikan, sekali untuk memeriksa hasil),
 * dan `create()` akan membuat tahun kedua sehingga id tidak pernah cocok.
 */
if (! function_exists('tahunDepanKenaikan')) {
    function tahunDepanKenaikan(): TahunAjaran
    {
        return TahunAjaran::firstOrCreate(
            ['nama_tahun_ajaran' => '2099/2100'],
            [
                'tanggal_mulai' => '2099-07-01',
                'tanggal_selesai' => '2100-06-30',
                'status_aktif' => false,
            ]
        );
    }
}

/**
 * Kelas 8A untuk tahun tujuan. `AkademikSeeder` hanya membuat tingkat 7.
 */
if (! function_exists('kelasDelapan')) {
    function kelasDelapan(): Kelas
    {
        return Kelas::firstOrCreate(['nama_kelas' => '8A'], ['tingkat' => '8']);
    }
}

/**
 * Satu siswa aktif di 7A pada tahun yang sedang berjalan.
 */
if (! function_exists('siswaTujuhA')) {
    function siswaTujuhA(string $kode): array
    {
        $rombel = Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))->firstOrFail();

        $user = User::factory()->create(['username' => "siswa-naik-{$kode}"]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => '7'.$kode,
            'nisn' => '0077'.str_pad($kode, 6, '0', STR_PAD_LEFT),
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

        return [$siswa, $rombel];
    }
}

/**
 * Jalankan kenaikan lewat Action sungguhan, termasuk salin rombel supaya
 * kelas tujuan punya rombel — sama seperti wizard.
 *
 * @return array<int, array{Siswa}>
 */
function jalankanKenaikan(string $prefix, int $jumlah): array
{
    $asal = TahunAjaran::aktif()->firstOrFail();
    $tujuan = tahunDepanKenaikan();
    $rombel7A = Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))->firstOrFail();

    $siswas = [];
    for ($i = 1; $i <= $jumlah; $i++) {
        [$siswa] = siswaTujuhA($prefix.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        $siswas[] = $siswa;
    }

    app(SalinRombelAction::class)->jalankan($asal->id, $tujuan->id);

    $rombel8A = Rombel::firstOrCreate([
        'kelas_id' => kelasDelapan()->id,
        'tahun_ajaran_id' => $tujuan->id,
    ]);

    app(ProsesKenaikanAction::class)->jalankan(
        $asal->id,
        $tujuan->id,
        [$rombel7A->kelas_id => (string) $rombel8A->kelas_id]
    );

    return $siswas;
}

/*
 * Guard utama.
 */

test('siswa yang sudah naik kelas tidak dianggap pointer tidak sinkron', function () {
    $siswas = jalankanKenaikan('71', 3);

    // Pointer sudah pindah ke 8A/tahun depan, baris 7A/tahun lama tetap
    // `aktif`. Itu benar, dan tidak boleh dibaca sebagai ketidaksesuaian.
    foreach ($siswas as $siswa) {
        $fresh = $siswa->fresh();

        expect($fresh->kelas_id)->toBe(kelasDelapan()->id)
            ->and($fresh->tahun_ajaran_id)->toBe(tahunDepanKenaikan()->id)
            ->and($fresh->riwayatKelas()->where('status', 'aktif')->count())->toBe(2);
    }

    $html = $this->actingAs(adminKenaikan())
        ->get(route('admin.riwayat-kelas.index', ['filter' => 'drift']))
        ->assertOk()
        ->getContent();

    foreach ($siswas as $siswa) {
        expect($html)->not->toContain($siswa->user->name);
    }

    expect($html)->toContain('Tidak ada siswa dengan masalah ini.');
});

test('siswa pasca kenaikan tampil sebagai sehat di daftar', function () {
    [$siswa] = jalankanKenaikan('72', 1);

    $html = $this->actingAs(adminKenaikan())
        ->get(route('admin.riwayat-kelas.index'))
        ->assertOk()
        ->getContent();

    $baris = barisRiwayatKelas($html, $siswa->user->name);

    expect($baris)->toContain('Sehat')
        ->and($baris)->not->toContain('Pointer tidak sinkron');
});

test('halaman daftar tidak menandai siswa yang naik kelas sebagai bermasalah', function () {
    jalankanKenaikan('73', 3);

    // Ringkasan di atas daftar: kalau semua siswa yang naik kelas ikut
    // terhitung, angka "perlu diperiksa" akan bukan nol padahal tidak ada
    // satu pun masalah.
    $html = $this->actingAs(adminKenaikan())
        ->get(route('admin.riwayat-kelas.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('Semua siswa punya riwayat yang sinkron');
});

/*
 * Drift yang sungguhan tetap harus tertangkap.
 */

test('kelas yang beda dalam tahun yang sama tetap dianggap drift', function () {
    $rombel7A = Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))->firstOrFail();
    $rombel7B = Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7B'))->firstOrFail();

    [$siswa] = siswaTujuhA('74');

    // Baris `aktif` ada di 7B, pointer menunjuk 7A, dan keduanya tahun sama.
    $siswa->riwayatKelas()->create([
        'kelas_id' => $rombel7B->kelas_id,
        'tahun_ajaran_id' => $rombel7A->tahun_ajaran_id,
        'status' => 'aktif',
    ]);

    $html = $this->actingAs(adminKenaikan())
        ->get(route('admin.riwayat-kelas.index', ['filter' => 'drift']))
        ->assertOk()
        ->getContent();

    expect($html)->toContain($siswa->user->name)
        ->and($html)->toContain('Pointer tidak sinkron');
});

test('dua baris aktif di tahun yang sama tetap dianggap dobel', function () {
    $rombel7A = Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))->firstOrFail();
    $rombel7B = Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7B'))->firstOrFail();

    [$siswa] = siswaTujuhA('75');

    $siswa->riwayatKelas()->create([
        'kelas_id' => $rombel7B->kelas_id,
        'tahun_ajaran_id' => $rombel7A->tahun_ajaran_id,
        'status' => 'aktif',
    ]);

    $html = $this->actingAs(adminKenaikan())
        ->get(route('admin.riwayat-kelas.index', ['filter' => 'ganda-aktif']))
        ->assertOk()
        ->getContent();

    expect($html)->toContain($siswa->user->name)
        ->and($html)->toContain('Dua baris aktif');
});

/*
 * Konteks untuk baris `aktif` dari tahun yang sudah lewat.
 */

test('baris aktif dari tahun yang lewat diberi keterangan, kelas sekarang tidak', function () {
    [$siswa] = jalankanKenaikan('76', 1);
    $tahunDepan = tahunDepanKenaikan();

    // Baris tahun lama harus tetap `aktif` — inilah yang perlu dijelaskan ke
    // admin, bukan diubah. Kalau nilainya sudah bukan `aktif`, keterangan di UI
    // jadi tidak relevan lagi.
    $barisLama = $siswa->riwayatKelas()
        ->where('tahun_ajaran_id', '!=', $tahunDepan->id)
        ->where('status', 'aktif')
        ->first();

    expect($barisLama)->not->toBeNull();

    $html = $this->actingAs(adminKenaikan())
        ->get(route('admin.riwayat-kelas.show', $siswa))
        ->assertOk()
        ->getContent();

    // Ada dua baris `aktif` (7A/tahun lalu + 8A/tahun depan), tapi hanya satu
    // yang berasal dari tahun lalu, jadi hanya itu yang memunculkan keterangan.
    expect(substr_count($html, 'kelas waktu itu, bukan kelas sekarang'))->toBe(1)
        ->and($html)->toContain($tahunDepan->nama_tahun_ajaran);
});

test('siswa tanpa kenaikan tidak melihat keterangan tahun archive', function () {
    [$siswa] = siswaTujuhA('77');

    $html = $this->actingAs(adminKenaikan())
        ->get(route('admin.riwayat-kelas.show', $siswa))
        ->assertOk()
        ->getContent();

    // Barisnya di tahun aktif, jadi tidak perlu keterangan apa pun.
    expect($html)->not->toContain('kelas waktu itu, bukan kelas sekarang');
});

/**
 * Potong satu baris <tr> berdasarkan nama siswa.
 */
if (! function_exists('barisRiwayatKelas')) {
    function barisRiwayatKelas(string $html, string $nama): string
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
}
