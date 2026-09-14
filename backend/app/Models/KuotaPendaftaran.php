<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KuotaPendaftaran extends Model
{
    use HasFactory;

    protected $fillable = [
        'tahun_ajaran_id',
        'jalur_pendaftaran_id',
        'kuota',
        'terisi',
        'keterangan'
    ];

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function jalurPendaftaran()
    {
        return $this->belongsTo(JalurPendaftaran::class, 'jalur_pendaftaran_id');
    }
}
