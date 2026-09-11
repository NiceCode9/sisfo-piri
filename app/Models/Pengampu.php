<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengampu extends Model
{
    /**
     * Jangkar nilai/materi/tugas/absensi: selalu merujuk baris ini
     * (guru + mapel + kelas + tahun), bukan guru langsung, agar
     * histori utuh saat guru bertukar atau siswa naik kelas.
     */
    protected $fillable = [
        'guru_id',
        'mata_pelajaran_id',
        'kelas_id',
        'tahun_ajaran_id',
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

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * Jalur baru (spike rombel): jangkar kelas berjalan.
     * Nullable selama masa transisi; jalur lama tetap berfungsi.
     */
    public function rombel()
    {
        return $this->belongsTo(Rombel::class);
    }
}
