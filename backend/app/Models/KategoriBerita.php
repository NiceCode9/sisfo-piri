<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriBerita extends Model
{
    use HasFactory;

    protected $table = 'kategori_beritas';

    protected $fillable = [
        'nama',
        'slug',
        'deskripsi',
    ];

    public function beritas(): HasMany
    {
        return $this->hasMany(Berita::class);
    }
}
