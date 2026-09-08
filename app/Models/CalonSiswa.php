<?php

namespace App\Models;

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
}
