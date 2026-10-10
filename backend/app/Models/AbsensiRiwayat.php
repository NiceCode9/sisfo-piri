<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak perubahan terhadap baris absensi yang sudah tercatat.
 *
 * Baris baru tidak dicatat di sini — kehadirannya sudah ada di `absensis`.
 * Yang masuk hanyalah saat `status` atau `jam_datang` berubah, karena itulah
 * yang tidak bisa ditebak kembali setelah kejadiannya lewat.
 */
class AbsensiRiwayat extends Model
{
    /**
     * Ditegaskan karena inflector bahasa Inggris tidak mengenal "riwayat" dan
     * akan mengubahnya menjadi `absensi_riwayats`. Pola yang sama dipakai
     * `GelombangTahap` dan `Pengaturan`.
     */
    protected $table = 'absensi_riwayat';

    protected $fillable = [
        'absensi_id',
        'siswa_id',
        'rombel_id',
        'tanggal',
        'status_sebelum',
        'status_sesudah',
        'jam_sebelum',
        'jam_sesudah',
        'alasan',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function absensi(): BelongsTo
    {
        return $this->belongsTo(Absensi::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
