<?php

use App\Models\Guru;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/** JPEG uji tanpa file contoh di repo. */
function jpegUji(int $lebar, int $tinggi): string
{
    $gambar = imagecreatetruecolor($lebar, $tinggi);
    ob_start();
    imagejpeg($gambar);
    $isi = ob_get_clean();
    imagedestroy($gambar);

    return $isi;
}

test('foto guru disajikan sebagai versi kecil', function () {
    Storage::fake('public');
    Storage::disk('public')->put('guru/uji.jpg', jpegUji(1200, 1600));

    $guru = Guru::factory()->create(['foto_path' => 'guru/uji.jpg']);

    $response = $this->get(route('spmb.guru.foto', $guru));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('image/jpeg');

    $thumb = Storage::disk('public')->path('guru/thumbs/uji.jpg');
    expect(file_exists($thumb))->toBeTrue();

    [$lebar, $tinggi] = getimagesize($thumb);
    expect($lebar)->toBe(800)
        ->and($tinggi)->toBeGreaterThan(800);
});

test('halaman guru memakai route foto versi kecil', function () {
    Storage::fake('public');
    Storage::disk('public')->put('guru/uji.jpg', jpegUji(1200, 1600));

    $guru = Guru::factory()->create(['nama' => 'Guru Uji', 'foto_path' => 'guru/uji.jpg']);

    $this->get(route('spmb.guru'))
        ->assertOk()
        ->assertSee(route('spmb.guru.foto', $guru));
});

test('foto yang tidak ada di disk menghasilkan 404', function () {
    Storage::fake('public');
    $guru = Guru::factory()->create(['foto_path' => 'guru/hilang.jpg']);

    $this->get(route('spmb.guru.foto', $guru))->assertNotFound();
});
