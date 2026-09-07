<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalPpdb extends Model
{
    protected $fillable = [
        'tahun_ajaran_id',
        'nama_jadwal',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan'
    ];

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }
}
