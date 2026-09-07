<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;

    protected $fillable = [
        'calon_siswa_id',
        'biaya_pendaftaran_id',
        'detail_angsuran_id',
        'kode_pembayaran',
        'jumlah',
        'metode_pembayaran',
        'jenis_pembayaran', // penuh, dp_angsuran, cicilan_angsuran
        'keterangan_angsuran',
        'bukti_pembayaran_path',
        'tanggal_pembayaran',
        'status',
        'catatan'
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
        return $this->belongsTo(DetailAngsuran::class);
    }
}
