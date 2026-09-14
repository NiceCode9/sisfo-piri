<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gelombang extends Model
{
    use HasFactory;

    protected $fillable = [
        'tahun_ajaran_id',
        'nama_gelombang',
        'nomor_urut',
        'badge',
        'tanggal_buka',
        'tanggal_tutup',
        'tanggal_tes',
        'tanggal_pengumuman',
        'kuota',
        'terisi',
        'diskon_persen',
        'keuntungan',
        'keterangan',
        'warna_border',
        'is_aktif',
    ];

    protected $casts = [
        'tanggal_buka' => 'date',
        'tanggal_tutup' => 'date',
        'tanggal_tes' => 'date',
        'tanggal_pengumuman' => 'date',
        'keuntungan' => 'array',
        'is_aktif' => 'boolean',
    ];

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }

    public function getPersentaseAttribute(): float
    {
        if ($this->kuota <= 0) {
            return 0;
        }

        return round($this->terisi / $this->kuota * 100, 1);
    }
}
