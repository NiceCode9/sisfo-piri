<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    protected $fillable = [
        'user_id',
        'nip',
        'nama',
        'jenis_kelamin',
        'telp',
        'alamat',
        'is_aktif',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pengampus()
    {
        return $this->hasMany(Pengampu::class);
    }

    public function waliKelas()
    {
        return $this->hasMany(WaliKelas::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }
}
