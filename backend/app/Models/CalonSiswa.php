<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CalonSiswa extends Model
{
    protected $fillable = [
        'jalur_pendaftaran_id',
        'user_id',
        'no_pendaftaran',
        'nik',
        'nisn',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'alamat',
        'no_hp',
        'email',
        'asal_sekolah',
        'nama_ayah',
        'pekerjaan_ayah',
        'nama_ibu',
        'pekerjaan_ibu',
        'no_hp_orang_tua',
        'tahun_ajaran_id',
        'status_pendaftaran',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function jalurPendaftaran()
    {
        return $this->belongsTo(JalurPendaftaran::class);
    }

    public function berkasCalonSiswa()
    {
        return $this->hasOne(BerkasCalonSiswa::class);
    }

    public function sertifikatPrestasis()
    {
        return $this->hasMany(SertifikatPrestasi::class);
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function logStatusPendaftaran()
    {
        return $this->hasMany(LogStatusPendaftaran::class);
    }

    public function siswa()
    {
        return $this->hasOne(Siswa::class);
    }

    public function pembayaranLainnya()
    {
        return $this->hasMany(PembayaranLainnya::class);
    }

    public function rencanaAngsuran()
    {
        return $this->hasMany(RencanaAngsuran::class);
    }

    /**
     * Calon dengan sisa tagihan: total pembayaran berhasil
     * masih di bawah total biaya wajib tahun ajarannya.
     * Semua status masuk; yang sudah lunas tersaring keluar.
     */
    public function scopeBelumLunas(Builder $query): Builder
    {
        // Tagihan induk yang ikut berhasil saat rencananya lunas dikecualikan
        // (DP + cicilannya sudah tercatat sendiri) agar tidak terhitung ganda.
        return $query->whereRaw(
            'COALESCE((SELECT SUM(jumlah) FROM pembayarans WHERE pembayarans.calon_siswa_id = calon_siswas.id AND pembayarans.status = ? AND NOT EXISTS (SELECT 1 FROM rencana_angsurans WHERE rencana_angsurans.pembayaran_id = pembayarans.id AND rencana_angsurans.status = ?)), 0) < COALESCE((SELECT SUM(jumlah) FROM biaya_pendaftarans WHERE biaya_pendaftarans.tahun_ajaran_id = calon_siswas.tahun_ajaran_id AND biaya_pendaftarans.wajib_bayar = 1), 0)',
            ['berhasil', 'lunas']
        );
    }
}
