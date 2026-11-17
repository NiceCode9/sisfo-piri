<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Fasilitas extends Model
{
    protected $fillable = [
        'jenis',
        'nama',
        'deskripsi',
        'fotos',
        'aktif',
        'urutan',
    ];

    protected $casts = [
        'fotos' => 'array',
        'aktif' => 'boolean',
        'urutan' => 'integer',
    ];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function scopeUrut(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy('nama');
    }
}
