<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Berita extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'beritas';

    protected $fillable = [
        'user_id',
        'kategori_berita_id',
        'judul',
        'slug',
        'ringkasan',
        'konten',
        'gambar_utama',
        'thumbnail',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'views_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'views_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function penulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kategoriBerita(): BelongsTo
    {
        return $this->belongsTo(KategoriBerita::class);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBerita::class, 'kategori_berita_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'berita_tag');
    }

    public function beritaGambars(): HasMany
    {
        return $this->hasMany(BeritaGambar::class);
    }

    public function gambars(): HasMany
    {
        return $this->hasMany(BeritaGambar::class);
    }

    public function komentars(): HasMany
    {
        return $this->hasMany(Komentar::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'published')
            ->where('published_at', '>', now());
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }
}
