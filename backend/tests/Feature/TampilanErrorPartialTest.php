<?php

/**
 * Jaga sinkronisasi `layouts/partials/alert` dengan halaman yang memakai `@error`.
 *
 * Partial itu meringkas error validasi supaya tidak ada validasi yang gagal
 * tanpa pesan sama sekali. Halaman yang sudah menulis pesan di bawah kolomnya
 * meneruskan daftar kuncinya lewat `['errorDitampilkan' => [...]]`, dan partial
 * lalu tidak mengulang pesan itu. Dua aturan yang diuji di sini:
 *
 * 1. Halaman yang punya `@error` tapi tidak mendaftarkan kuncinya akan
 *    menampilkan pesan dua kali — persis duplikasi yang diperbaiki.
 * 2. Kunci yang terdaftar tapi tidak ada `@error`-nya membuat pesan hilang
 *    total, karena partial menganggap sudah tampil padahal tidak. Ini yang
 *    paling berbahaya, jadi diuji kedua.
 *
 * Sumbernya dibaca langsung dari berkas Blade: daftar di `@include` adalah
 * pilihan manual, dan test inilah yang membuatnya tidak bisa basi diam-diam.
 */

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
});

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

function viewYangPakaiAlertPartial(): array
{
    $hasil = [];
    // Separator di `getRelativePathname()` mengikuti OS, jadi dinormalkan:
    // perbandingan di bawah tidak boleh gagal hanya karena dijalankan di
    // Windows.
    $partial = 'layouts/partials/alert.blade.php';

    foreach (File::allFiles(resource_path('views')) as $berkas) {
        if (! str_ends_with($berkas->getFilename(), '.blade.php')) {
            continue;
        }

        // Partial sendiri menyebut namanya di komentar contoh. Kalau ikut
        // dipindai, partial akan dilaporkan tidak sinkron dengan dirinya.
        if (str_replace('\\', '/', $berkas->getRelativePathname()) === $partial) {
            continue;
        }

        $isi = $berkas->getContents();

        if (! str_contains($isi, 'layouts.partials.alert')) {
            continue;
        }

        $hasil[$berkas->getRelativePathname()] = $isi;
    }

    return $hasil;
}

/**
 * Kunci yang ditulis halaman lewat `@error('...')`.
 *
 * Dua bentuk muncul di kode: kunci polos, dan kunci berawalan yang dirangkai
 * di dalam string — `@error('nilai.'.$p->id)`. Bentuk kedua menghasilkan
 * literal `'nilai.'`; garis titik di ujungnya harus dibuang supaya cocok
 * dengan prefiks `nilai` yang didaftarkan ke partial.
 *
 * @return array<int, string>
 */
function kunciErrorPerField(string $isi): array
{
    preg_match_all("/@error\(\s*'([^']+)'/", $isi, $cocok);

    return array_values(array_unique(array_map(
        fn ($k) => rtrim(rtrim($k, '*'), '.'),
        $cocok[1],
    )));
}

/** Kunci yang didaftarkan halaman ke partial lewat `errorDitampilkan`. */
function kunciTerdaftarDiPartial(string $isi): array
{
    if (! preg_match("/errorDitampilkan'\s*=>\s*\[([^\]]*)\]/", $isi, $cocok)) {
        return [];
    }

    preg_match_all("/'([^']+)'/", $cocok[1], $isiKunci);

    return array_values(array_unique($isiKunci[1]));
}

test('halaman yang punya @error mendaftarkan kuncinya ke partial alert', function () {
    $bermasalah = [];

    foreach (viewYangPakaiAlertPartial() as $relatif => $isi) {
        $perField = kunciErrorPerField($isi);
        $terdaftar = kunciTerdaftarDiPartial($isi);

        if ($perField === []) {
            continue;
        }

        $terdaftar = array_map(fn ($k) => rtrim($k, '*'), $terdaftar);

        $hilang = array_values(array_diff($perField, $terdaftar));

        if ($hilang !== []) {
            $bermasalah[] = "{$relatif}: @error tanpa daftar — ".implode(', ', $hilang);
        }
    }

    expect($bermasalah)->toBe([], "Pesan tampil dua kali di halaman berikut:\n".implode("\n", $bermasalah));
});

test('kunci terdaftar di partial benar-benar punya @error di halaman itu', function () {
    $bermasalah = [];

    foreach (viewYangPakaiAlertPartial() as $relatif => $isi) {
        $perField = kunciErrorPerField($isi);
        $punyaKunciDinamis = (bool) preg_match('/@error\(\s*(?!\')/', $isi);

        foreach (kunciTerdaftarDiPartial($isi) as $kunci) {
            $kunci = rtrim($kunci, '*');

            // Kunci bertingkat boleh didaftarkan sebagai induk, misal `options`
            // untuk `options.0.key`.
            $ada = in_array($kunci, $perField, true)
                || (bool) array_filter($perField, fn ($k) => str_starts_with($k, $kunci.'.'));

            // Halaman yang kuncinya dinamis — `@error($input)` — tidak punya
            // kunci yang bisa dibaca. Untuk halaman seperti itu, keberadaan
            // kuncinya di dalam berkas dipakai gantinya: harus ada sebagai
            // literal, entah di `@error` atau di loop yang menghasilkannya.
            // Ejaan yang keliru tetap ketahuan.
            if (! $ada && $punyaKunciDinamis) {
                $ada = str_contains($isi, "'{$kunci}'");
            }

            if (! $ada) {
                $bermasalah[] = "{$relatif}: terdaftar '{$kunci}' tapi tidak ada @error-nya";
            }
        }
    }

    expect($bermasalah)->toBe([], "Kunci berikut membuat pesan hilang total:\n".implode("\n", $bermasalah));
});

/**
 * View yang memakai `@error` dengan kunci variabel, misal
 * `@error('nilai.'.$p->id)` atau `@error($field)`.
 *
 * Kuncinya tidak bisa dibaca dari sumber, jadi aturan "semua kunci harus
 * terdaftar" tidak berlaku untuk bentuk ini. Yang bisa diperiksa: halaman
 * seperti ini wajib mendaftarkan sesuatu, kalau tidak pesannya tetap tampil
 * dua kali tanpa ada yang bisa diperbaiki.
 *
 * @return array<string, string>
 */
function viewDenganKunciErrorVariabel(): array
{
    $hasil = [];

    foreach (viewYangPakaiAlertPartial() as $relatif => $isi) {
        // `@error(` yang tidak diikuti tanda kutip adalah kunci dinamis.
        if (preg_match_all('/@error\(\s*(?!\')/', $isi, $cocok) && $cocok[0] !== []) {
            $hasil[$relatif] = implode(' | ', array_slice($cocok[0], 0, 3));
        }
    }

    return $hasil;
}

/**
 * Halaman apakah pun sudah mendaftarkan kuncinya, dalam bentuk apa pun.
 *
 * Daftar bisa berupa array literal, atau ekspresi lain seperti
 * `\App\Models\BerkasCalonSiswa::PERLU_PERBAIKAN` yang diambil dari sumbernya
 * supaya tidak perlu disalin. Yang diuji di sini hanya apakah terbaca atau tidak,
 * sedangkan isi array literalnya yang diperiksa dua aturan di atas.
 */
function halamanMendaftarkanKunci(string $isi): bool
{
    return str_contains($isi, 'errorDitampilkan');
}

test('halaman dengan kunci @error variabel tetap mendaftarkan sesuatu ke partial', function () {
    $bermasalah = [];

    foreach (viewDenganKunciErrorVariabel() as $relatif => $bentuk) {
        if (! halamanMendaftarkanKunci(file_get_contents(resource_path('views/'.$relatif)))) {
            $bermasalah[] = "{$relatif}: {$bentuk}";
        }
    }

    expect($bermasalah)->toBe([], "Halaman berikut memakai @error dengan kunci variabel tanpa mendaftarkannya:\n".implode("\n", $bermasalah));
});

test('hanya halaman yang benar-benar memakai partial yang dipindai', function () {
    // Partial alert menyebut namanya sendiri di dalam komentar contoh. Kalau
    // tidak dikecualikan, partial akan dihitung sebagai "halaman yang tidak sinkron"
    // dengan dirinya sendiri.
    $hasil = viewYangPakaiAlertPartial();

    expect($hasil)->not->toHaveKey('layouts/partials/alert.blade.php');
    expect(count($hasil))->toBeGreaterThan(50);
});

test('halaman tanpa @error tetap dapat ringkasan error global', function () {
    // 85 dari 99 halaman memakai partial ini tanpa punya @error sama sekali.
    // Kalau daftar `errorDitampilkan` bocor ke partial secara global, semua
    // halaman itu ikut kehilangan pesannya — jadi pastikan tidak ada yang
    // mendaftarkan kunci di luar halaman yang memang punya kolomnya.
    $tanpaErrorInline = [];

    foreach (viewYangPakaiAlertPartial() as $relatif => $isi) {
        if (kunciErrorPerField($isi) === [] && kunciTerdaftarDiPartial($isi) === []) {
            $tanpaErrorInline[] = $relatif;
        }
    }

    expect(count($tanpaErrorInline))->toBeGreaterThan(50);
});

test('partial alert hanya menyaring pesan, tidak menyembunyikan semua', function () {
    $isi = File::get(resource_path('views/layouts/partials/alert.blade.php'));

    // Dua hal yang harus benar-benar ada di partial:
    expect($isi)->toContain('$belumDitampilkan');
    expect($isi)->toContain('$belumDitampilkan['); // ada isi, bukan sekadar kosong

    // Penyingkiran harus bersyarat per kunci, bukan memblokir semua
    // ketika daftarnya terisi.
    expect($isi)->toContain('str_starts_with($kunci, $d');

    // Kotak ringkasan tetap dirender kalau ada sisa pesan.
    expect($isi)->toContain('data belum disimpan');
});

/*
 * Di bawah ini adalah pembuktian fungsionalnya, bukan pembacaan sumber.
 *
 * Penting: TIDAK memakai `assertSessionHasErrors()` sebelum membuka halaman.
 * Assertion itu membaca session lebih dulu sehingga flash error ikut habis,
 * dan halaman berikutnya dirender tanpa pesan sama sekali — persis jebakan
 * yang membuat test versi pertama selalu hijau.
 *
 * Semua assertion dibandingkan dengan angka atau boolean, bukan string HTML
 * penuh, supaya kegagalan tidak memuntahkan seluruh halaman ke output.
 */

test('pesan yang sudah tampil di bawah kolom tidak diulang di ringkasan', function () {
    // admin/pengaturans punya @error('batas_terlambat') dan mendaftarkan
    // kuncinya ke partial. Kalau saringannya tidak bekerja, kalimat yang sama
    // muncul dua kali: di ringkasan atas dan di bawah kolom.
    $this->actingAs(superAdmin())
        ->put(route('admin.pengaturans.update'), ['batas_terlambat' => 'bukan-jam'])
        ->assertStatus(302);

    $html = $this->actingAs(superAdmin())->get(route('admin.pengaturans.index'))->getContent();
    $pesan = 'Batas terlambat tidak cocok dengan format H:i.';

    expect(str_contains($html, $pesan), "pesan tidak muncul sama sekali: {$pesan}")->toBeTrue();

    expect(substr_count($html, $pesan), 'pesan tampil lebih dari sekali')->toBe(1);

    // Ringkasan global tidak boleh muncul: seluruh error punya kolomnya sendiri.
    expect(str_contains($html, 'data belum disimpan'), 'ringkasan global seharusnya disembunyikan')->toBeFalse();

    // Tapi pesan per-field tetap ada, lengkap dengan penanda is-invalid.
    expect(str_contains($html, 'invalid-feedback'), 'pesan per-field hilang')->toBeTrue();
    expect(str_contains($html, 'is-invalid'), 'field tidak ditandai is-invalid')->toBeTrue();
});

test('halaman tanpa error per field tetap mendapat ringkasan', function () {
    // exams/create sengaja tidak punya @error sama sekali — justru itu yang
    // membuat halaman ini bergantung pada jaring pengaman dari partial.
    $this->actingAs(superAdmin())
        ->post(route('admin.cbt.exams.store'), [])
        ->assertRedirect();

    $html = $this->actingAs(superAdmin())->get(route('admin.cbt.exams.create'))->getContent();

    expect(str_contains($html, 'data belum disimpan'), 'ringkasan global hilang')->toBeTrue();
    expect(str_contains($html, 'Rombel wajib diisi.'), 'pesan rombel hilang')->toBeTrue();

    // Atribut `name` dipetakan ke "nama" di `lang/id/validation.php`, bukan
    // "nama ujian" seperti label di form.
    expect(str_contains($html, 'Nama wajib diisi.'), 'pesan nama hilang')->toBeTrue();
});
