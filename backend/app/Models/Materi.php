<?php

namespace App\Models;

use App\Http\Controllers\Elearning\BerkasController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Materi extends Model
{
    /**
     * Berkas materi tidak disimpan di disk `public`: URL `/storage/...`
     * bisa dibuka siapa pun tanpa login, termasuk berkas kelas yang belum
     * diumumkan. Penyajiannya lewat route terotorisasi di
     * {@see BerkasController}.
     */
    public const DISK = 'berkas';

    protected $fillable = [
        'rombel_id',
        'mata_pelajaran_id',
        'guru_id',
        'judul',
        'deskripsi',
        'tipe',
        'file_path',
        'url',
        'is_aktif',
    ];

    protected $casts = [
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

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }
}
