<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeritaGambar extends Model
{
    use HasFactory;

    protected $table = 'berita_gambar';

    protected $fillable = [
        'berita_id',
        'path',
        'caption',
        'urutan',
    ];

    protected $casts = [
        'urutan' => 'integer',
    ];

    public function berita(): BelongsTo
    {
        return $this->belongsTo(Berita::class);
    }
}
