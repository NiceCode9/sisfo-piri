<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengampu extends Model
{
    /**
     * Satu guru per mapel per rombel. Kelas + tahun dibaca
     * lewat relasi rombel (kolom pasangan lama sudah dilepas).
     */
    protected $fillable = [
        'guru_id',
        'mata_pelajaran_id',
        'rombel_id',
    ];

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    public function rombel()
    {
        return $this->belongsTo(Rombel::class);
    }
}
