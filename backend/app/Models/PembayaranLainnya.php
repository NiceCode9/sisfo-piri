<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranLainnya extends Model
{
    protected $fillable = [
        'calon_siswa_id',
        'kode_pembayaran',
        'nama_biaya',
        'jumlah',
        'metode_pembayaran',
        'bukti_pembayaran_path',
        'tanggal_pembayaran',
        'status',
        'catatan',
    ];

    public function calonSiswa()
    {
        return $this->belongsTo(CalonSiswa::class);
    }
}
