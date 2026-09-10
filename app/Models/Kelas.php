<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $fillable = [
        'nama_kelas',
        'tingkat',
        'deskripsi',
    ];

    public function pengampus()
    {
        return $this->hasMany(Pengampu::class);
    }

    public function waliKelas()
    {
        return $this->hasMany(WaliKelas::class);
    }

    public function riwayatKelas()
    {
        return $this->hasMany(RiwayatKelas::class);
    }

    public function siswas()
    {
        return $this->hasMany(Siswa::class);
    }
}
