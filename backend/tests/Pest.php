<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Buang nilai acak yang di-generate tiap request dari HTML halaman.
 *
 * Setiap halaman memuat token CSRF acak 40 karakter, baik di meta tag
 * `csrf-token` maupun di input tersembunyi `_token`. Assert string pendek
 * seperti `7B` langsung ke HTML rapuh: token itu memuat `7B` sekitar 0,9%
 * dari waktu ke waktu, sehingga test gagal sesekali tanpa ada perubahan kode
 * — persis itulah yang membuat suite tampak flaky padahal tidak ada bug.
 *
 * Token bukan bagian dari apa pun yang test-test ini periksa, jadi aman
 * dibuang. Dipakai begini:
 *
 *     $respons = $this->actingAs($user)->get(route('admin.absensis.index'));
 *     $respons->assertOk();
 *     expect(htmlTanpaToken($respons->getContent()))->not->toContain('7B');
 */
function htmlTanpaToken(string $html): string
{
    return preg_replace(
        '/(<meta name="csrf-token" content="|_token"\s+value=")([A-Za-z0-9]{20,})/',
        '$1TOKEN',
        $html
    );
}
