<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengumpulanTugas extends Model
{
    protected $fillable = [
        'tugas_id',
        'siswa_id',
        'file_path',
        'jawaban_text',
        'nilai',
        'catatan_guru',
        'is_terlambat',
    ];

    protected $casts = [
        'is_terlambat' => 'boolean',
    ];

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
