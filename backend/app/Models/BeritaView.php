<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeritaView extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'berita_views';

    protected $fillable = [
        'berita_id',
        'user_id',
        'ip_hash',
        'session_id',
        'duration_seconds',
        'created_at',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'created_at' => 'datetime',
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
