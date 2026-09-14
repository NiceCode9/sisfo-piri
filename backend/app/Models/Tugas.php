<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tugas extends Model
{
    protected $fillable = [
        'rombel_id',
        'mata_pelajaran_id',
        'guru_id',
        'judul',
        'deskripsi',
        'deadline',
        'is_aktif',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'is_aktif' => 'boolean',
    ];

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function pengumpulans(): HasMany
    {
        return $this->hasMany(PengumpulanTugas::class);
    }
}
