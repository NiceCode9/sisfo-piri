<?php

/*
 * Regression test untuk {@see htmlTanpaToken()}.
 *
 * Fungsi ini menjaga asersi berjarum pendek di seluruh suite tetap
 * deterministik dan tidak flakiness.
 */

test('token csrf dibuang dari meta tag dan input tersembunyi', function () {
    $html = '<meta name="csrf-token" content="k47BwHJ6QrHsYyARuBpt2J1niD6cf3abcd">'
        .'<input type="hidden" name="_token" value="6TNZ8m2DOSk47BwHJ6QrHsYyARuBpt2J">';

    $bersih = htmlTanpaToken($html);

    expect($bersih)->not->toContain('k47BwHJ6QrHsYyARuBpt2J1niD6cf3abcd')
        ->and($bersih)->not->toContain('6TNZ8m2DOSk47BwHJ6QrHsYyARuBpt2J');
});

test('isi halaman yang bukan token tetap utuh', function () {
    $html = '<option value="1">7A &mdash; 2026/2027</option>'
        .'<td style="font-weight:600;">Anak Rekap Satu</td>'
        .'<span class="badge-nexus badge-info">Hadir</span>';

    expect(htmlTanpaToken($html))->toBe($html);
});

test('nilai acak pendek tidak ikut terbuang', function () {
    // Class atau id markup tetap harus bisa diperiksa apa adanya.
    $html = '<div class="col-7B" id="8A">Isi</div>';

    expect(htmlTanpaToken($html))->toBe($html);
});

test('jarum pendek yang muncul karena token hilang dari pemeriksaan', function () {
    // Bukti mekanismenya: tanpa helper, token acak bisa memuat "7B" dan
    // membuat `assertDontSee('7B')` gagal tanpa ada perubahan kode.
    $token = '6TNZ8m2DOSk47BwHJ6QrHsYyARuBpt2J1niD6cf3';

    expect($token)->toContain('7B');
    expect(htmlTanpaToken('<meta name="csrf-token" content="'.$token.'">'))->not->toContain('7B');
});
