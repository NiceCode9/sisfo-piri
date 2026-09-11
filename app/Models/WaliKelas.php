<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ARSIP read-only: wali kelas kini tercatat di rombels.wali_guru_id.
 * Model dipertahankan agar riwayat lama tetap bisa dibaca.
 * Dilarang menulis dari kode baru (tidak ada CRUD).
 */
class WaliKelas extends Model
{
    protected $fillable = [
        'guru_id',
        'kelas_id',
        'tahun_ajaran_id',
    ];

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }
}
