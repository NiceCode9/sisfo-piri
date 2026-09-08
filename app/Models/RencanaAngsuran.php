<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RencanaAngsuran extends Model
{
    protected $fillable = [
        'calon_siswa_id',
        'biaya_pendaftaran_id',
        'kode_angsuran',
        'total_biaya',
        'dp_dibayar',
        'sisa_hutang',
        'jumlah_cicilan',
        'nominal_per_cicilan',
        'tanggal_mulai',
        'tanggal_selesai',
        'status', // aktif, tidak aktif
        'catatan',
    ];

    public function calonSiswa()
    {
        return $this->belongsTo(CalonSiswa::class);
    }

    public function biayaPendaftaran()
    {
        return $this->belongsTo(BiayaPendaftaran::class);
    }

    public function detailAngsuran()
    {
        return $this->hasMany(DetailAngsuran::class);
    }
}
