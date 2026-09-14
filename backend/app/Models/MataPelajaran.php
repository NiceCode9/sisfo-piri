<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MataPelajaran extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'kelompok',
        'kkm',
        'is_aktif',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
    ];

    public function pengampus()
    {
        return $this->hasMany(Pengampu::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }
}
