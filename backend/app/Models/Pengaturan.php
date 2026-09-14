<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $fillable = [
        'kunci',
        'nilai',
        'keterangan',
    ];

    public static function nilai(string $kunci, ?string $default = null): ?string
    {
        return static::where('kunci', $kunci)->first()?->nilai ?? $default;
    }
}
