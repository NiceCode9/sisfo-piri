<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SertifikatPrestasi extends Model
{
    protected $fillable = [
        'calon_siswa_id',
        'nama_sertifikat',
        'file_path',
    ];

    public function calonSiswa()
    {
        return $this->belongsTo(CalonSiswa::class);
    }
}
