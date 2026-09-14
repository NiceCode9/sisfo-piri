<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailAngsuran extends Model
{
     protected $fillable = [
        'rencana_angsuran_id',
        'cicilan_ke',
        'nominal_cicilan',
        'tanggal_jatuh_tempo',
        'denda',
        'total_bayar',
        'tanggal_bayar',
        'status', // belum bayar, sudah bayar
        'catatan',
    ];

    public function rencanaAngsuran()
    {
        return $this->belongsTo(RencanaAngsuran::class);
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class);
    }
}
