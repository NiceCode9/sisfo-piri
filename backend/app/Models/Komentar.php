<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Komentar extends Model
{
    use HasFactory;

    protected $table = 'komentars';

    protected $fillable = [
        'berita_id',
        'user_id',
        'nama',
        'email',
        'isi',
        'status',
        'ip_address',
    ];

    public function berita(): BelongsTo
    {
        return $this->belongsTo(Berita::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
