<?php

use App\Models\Berita;
use App\Models\KategoriBerita;
use App\Models\Komentar;
use App\Models\Like;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('berita factory creates published scope correctly', function () {
    $user = User::factory()->create();
    $kategori = KategoriBerita::factory()->create();
    $berita = Berita::factory()->published()->create([
        'user_id' => $user->id,
        'kategori_berita_id' => $kategori->id,
    ]);

    expect(Berita::published()->count())->toBe(1);
    expect(Berita::draft()->count())->toBe(0);
    expect(Berita::scheduled()->count())->toBe(0);
    expect($berita->is_published)->toBeTrue();
    expect($berita->penulis->id)->toBe($user->id);
    expect($berita->kategoriBerita->id)->toBe($kategori->id);
});

test('berita scheduled and draft scopes', function () {
    Berita::factory()->scheduled()->create();
    Berita::factory()->draft()->create();

    expect(Berita::published()->count())->toBe(0);
    expect(Berita::scheduled()->count())->toBe(1);
    expect(Berita::draft()->count())->toBe(1);
});

test('berita soft deletes', function () {
    $berita = Berita::factory()->create();
    $berita->delete();

    expect(Berita::count())->toBe(0);
    expect(Berita::withTrashed()->count())->toBe(1);
});

test('tag belongs to many berita', function () {
    $berita = Berita::factory()->create();
    $tag = Tag::factory()->create();
    $berita->tags()->attach($tag);

    expect($berita->tags()->count())->toBe(1);
    expect($tag->beritas()->count())->toBe(1);
});

test('berita gambar, komentar, like relationships', function () {
    $berita = Berita::factory()->create();
    $gambar = $berita->gambars()->create(['path' => 'x.jpg', 'urutan' => 1]);
    $komentar = Komentar::factory()->create(['berita_id' => $berita->id]);
    $likeUser = User::factory()->create();
    $like = Like::create(['berita_id' => $berita->id, 'user_id' => $likeUser->id]);

    expect($berita->beritaGambars()->count())->toBe(1);
    expect($berita->komentars()->count())->toBe(1);
    expect($berita->likes()->count())->toBe(1);
    expect($gambar->berita->id)->toBe($berita->id);
    expect($komentar->berita->id)->toBe($berita->id);
    expect($like->berita->id)->toBe($berita->id);
});

test('komentar scopes', function () {
    $berita = Berita::factory()->create();
    Komentar::factory()->create(['berita_id' => $berita->id, 'status' => 'pending']);
    Komentar::factory()->create(['berita_id' => $berita->id, 'status' => 'approved']);

    expect(Komentar::pending()->count())->toBe(1);
    expect(Komentar::approved()->count())->toBe(1);
});
