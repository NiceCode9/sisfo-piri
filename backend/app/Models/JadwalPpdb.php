<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalPpdb extends Model
{
    public const TIPE = [
        'pendaftaran' => 'Pendaftaran',
        'verifikasi' => 'Verifikasi Berkas',
        'tes' => 'Tes Seleksi',
        'pengumuman' => 'Pengumuman',
        'daftar_ulang' => 'Daftar Ulang',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'tahun_ajaran_id',
        'nama_jadwal',
        'tipe',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function scopeTipe(Builder $query, string $tipe): Builder
    {
        return $query->where('tipe', $tipe);
    }

    public function getLabelTipeAttribute(): string
    {
        return self::TIPE[$this->tipe] ?? $this->tipe;
    }

    public function sedangBerlangsung(): bool
    {
        $hari = now()->startOfDay();

        return $this->tanggal_mulai->startOfDay()->lte($hari)
            && $this->tanggal_selesai->endOfDay()->gte($hari);
    }

    /**
     * Jendela pendaftaran untuk satu tahun ajaran.
     *
     * Mengembalikan null bila admin belum menandai baris bertipe `pendaftaran`.
     * Null berarti "tidak ada gate" — pendaftaran tetap dibuka supaya variation
     * tanggal/tipe tidak bisa mengunci sekolah tanpa sengaja.
     */
    public static function jendelaPendaftaran(?TahunAjaran $tahun): ?self
    {
        if (! $tahun) {
            return null;
        }

        return self::where('tahun_ajaran_id', $tahun->id)
            ->tipe('pendaftaran')
            ->orderBy('tanggal_mulai')
            ->first();
    }
}
