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
        'kuota_pendaftaran',
        'terisi_pendaftaran',
        'keterangan',
    ];

    protected $casts = [
        'kuota' => 'integer',
        'terisi' => 'integer',
        'kuota_pendaftaran' => 'integer',
        'terisi_pendaftaran' => 'integer',
    ];

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function jalurPendaftaran()
    {
        return $this->belongsTo(JalurPendaftaran::class, 'jalur_pendaftaran_id');
    }

    /**
     * Kuota pendaftaran penuh? NULL berarti tidak dibatasi.
     *
     * Berlaku untuk jalur yang tidak membatasi jumlah pendaftar, misalnya Jalur
     * Reguler yang menerima berapa pun pendaftar selama ada gelombang terbuka.
     */
    public function pendaftaranPenuh(): bool
    {
        return $this->kuota_pendaftaran !== null
            && $this->terisi_pendaftaran >= $this->kuota_pendaftaran;
    }

    /**
     * Kuota penerimaan penuh?
     *
     * NULL berarti "tanpa batas", sama seperti `pendaftaranPenuh()` dan sama
     * seperti `Gelombang::kuotaPenuh()`. Inilah yang membuat Jalur Reguler
     * bisa menerima tanpa batas sementara Jalur Prestasi dibatasi angkanya.
     *
     * Angka nol TIDAK boleh dipakai sebagai penanda. Dulu form mengizinkan
     * `min:0`, dan `0` di sini berarti `0 >= 0` = selalu penuh — jalur yang
     * diartikan admin sebagai "tanpa batas" malah menutup dirinya sendiri.
     */
    public function penerimaanPenuh(): bool
    {
        return $this->kuota !== null && $this->terisi >= $this->kuota;
    }

    /**
     * Ambil baris kuota terkunci untuk satu jalur pada tahun ajaran tertentu.
     */
    public static function kunci(int $tahunAjaranId, int $jalurPendaftaranId): ?self
    {
        return self::where('tahun_ajaran_id', $tahunAjaranId)
            ->where('jalur_pendaftaran_id', $jalurPendaftaranId)
            ->lockForUpdate()
            ->first();
    }
}
