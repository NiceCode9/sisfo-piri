<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiayaPendaftaran extends Model
{
    use HasFactory;

    protected $fillable = [
        'tahun_ajaran_id',
        'jenis_biaya',
        'jumlah',
        'mata_uang',
        'wajib_bayar',
        'dapat_diangsur',
        'max_cicilan',
        'min_dp',
        'jangka_waktu_hari',
        'keterangan',
    ];

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function rencanaAngsuran()
    {
        return $this->hasMany(RencanaAngsuran::class);
    }
}
