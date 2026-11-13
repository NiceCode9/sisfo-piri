<?php

use App\Models\Berita;
use App\Models\KategoriBerita;
use App\Models\Komentar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('/berita returns 200 and shows published berita', function () {
    $user = User::factory()->create();
    $kategori = KategoriBerita::factory()->create();
    $published = Berita::factory()->published()->create([
        'user_id' => $user->id,
        'kategori_berita_id' => $kategori->id,
        'judul' => 'Berita Publik Test',
    ]);
    $draft = Berita::factory()->draft()->create([
        'user_id' => $user->id,
        'kategori_berita_id' => $kategori->id,
        'judul' => 'Berita Draft Rahasia',
    ]);

    $response = $this->get(route('berita.index'));

    $response->assertOk()->assertSee($published->judul);
    expect($response->getContent())->not->toContain($draft->judul);
});

test('/berita/{slug} returns 200 for published berita', function () {
    $user = User::factory()->create();
    $kategori = KategoriBerita::factory()->create();
    $published = Berita::factory()->published()->create([
        'user_id' => $user->id,
        'kategori_berita_id' => $kategori->id,
    ]);

    $this->get(route('berita.show', $published))->assertOk()->assertSee($published->judul);
});

test('/berita/{slug} returns 404 for draft berita', function () {
    $user = User::factory()->create();
    $kategori = KategoriBerita::factory()->create();
    $draft = Berita::factory()->draft()->create([
        'user_id' => $user->id,
        'kategori_berita_id' => $kategori->id,
    ]);

    $this->get(route('berita.show', $draft))->assertNotFound();
});

test('tamu bisa kirim komentar yang menunggu persetujuan', function () {
    $berita = Berita::factory()->published()->create();

    $this->post(route('berita.komentar.store', $berita), [
        'nama' => 'Tamu',
        'email' => 'tamu@example.com',
        'isi' => 'Komentar tamu',
    ])->assertRedirect();

    expect(Komentar::where('berita_id', $berita->id)->first())
        ->status->toBe('pending')
        ->isi->toBe('Komentar tamu');
});

test('sitemap memuat berita yang sudah terbit', function () {
    $berita = Berita::factory()->published()->create(['judul' => 'Berita Sitemap']);

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee(route('berita.show', $berita), false);
});
