<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Galeri extends Model
{
    protected $fillable = [
        'tipe',
        'title',
        'desc',
        'image_path',
        'tanggal',
        'order',
        'is_active',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'is_active' => 'boolean',
    ];

    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFoto($query)
    {
        return $query->where('tipe', 'galeri');
    }

    public function scopePrestasi($query)
    {
        return $query->where('tipe', 'prestasi');
    }
}
