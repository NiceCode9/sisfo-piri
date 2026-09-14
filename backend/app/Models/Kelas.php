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

    public function rombels()
    {
        return $this->hasMany(Rombel::class);
    }

    public function pengampus()
    {
        return $this->hasManyThrough(Pengampu::class, Rombel::class, 'kelas_id', 'rombel_id');
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
