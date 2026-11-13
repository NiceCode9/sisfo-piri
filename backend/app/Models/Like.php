<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Like extends Model
{
    use HasFactory;

    protected $table = 'likes';

    protected $fillable = [
        'berita_id',
        'user_id',
    ];

    public function berita(): BelongsTo
    {
        return $this->belongsTo(Berita::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
