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
     * Kuota pendaftaran penuh? `kuota_pendaftaran` NULL berarti tidak dibatasi.
     */
    public function pendaftaranPenuh(): bool
    {
        return $this->kuota_pendaftaran !== null
            && $this->terisi_pendaftaran >= $this->kuota_pendaftaran;
    }

    /**
     * Kuota penerimaan penuh?
     *
     * `kuota` NULL diperlakukan sebagai "tanpa batas" agar konsisten dengan
     * `pendaftaranPenuh()` yang memakai NULL = tidak dibatasi.
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
