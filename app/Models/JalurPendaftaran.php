<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JalurPendaftaran extends Model
{
    protected $fillable = [
        'nama_jalur',
        'deskripsi',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function kuotaPendaftaran()
    {
        return $this->hasMany(KuotaPendaftaran::class);
    }

    public function calonSiswa()
    {
        return $this->hasMany(CalonSiswa::class);
    }
}
