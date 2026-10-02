<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kalender fase PPDB milik sekolah.
 *
 * Catatan penting: kelas ini TIDAK lagi meng-gate pendaftaran. Gate itu
 * sepenuhnya milik `Gelombang` (lihat `App\Support\StatusPendaftaran`).
 * Tanpa ini ada dua sumber untuk satu fakta - "kapan pendaftaran dibuka" -
 * dan keduanya bisa berbeda: form terbuka sementara landing page menulis
 * "Pendaftaran Ditutup".
 *
 * Yang tersisa di sini murni penampil: kalender internal sekolah, tidak
 * ditampilkan di halaman publik.
 */
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
}
