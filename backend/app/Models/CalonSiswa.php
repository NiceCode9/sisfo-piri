<?php

namespace App\Models;

use App\Support\TagihanCalon;
use Illuminate\Database\Eloquent\Model;

class CalonSiswa extends Model
{
    /**
     * Daftar agama baku. Satu-satunya sumber nilai ini dipakai form publik,
     * form admin, dan aturan validasi — supaya nilai yang masuk lewat form
     * publik selalu cocok dengan pilihan di form admin.
     */
    public const AGAMA = [
        'Islam',
        'Kristen',
        'Katolik',
        'Hindu',
        'Buddha',
        'Khonghucu',
    ];

    protected $fillable = [
        'jalur_pendaftaran_id',
        'gelombang_id',
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

    public function gelombang()
    {
        return $this->belongsTo(Gelombang::class);
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
     * Ringkasan tagihan (total, terbayar, sisa, rincian per biaya).
     *
     * Definisi "lunas" hanya ada di `App\Support\TagihanCalon` supaya halaman
     * detail dan pemilih pembayaran tidak pernah berbeda jawaban.
     */
    public function tagihan(): TagihanCalon
    {
        return TagihanCalon::untuk($this);
    }
}
