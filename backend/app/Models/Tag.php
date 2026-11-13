<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $table = 'tags';

    protected $fillable = [
        'nama',
        'slug',
    ];

    public function beritas(): BelongsToMany
    {
        return $this->belongsToMany(Berita::class, 'berita_tag');
    }
}
