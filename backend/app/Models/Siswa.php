<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Siswa extends Model
{
    use HasFactory;

    protected $fillable = [
        'calon_siswa_id',
        'user_id',
        'nis',
        'nisn',
        'qr_token',
        'foto_path',
        'tahun_ajaran_id',
        'kelas_id',
        'tanggal_diterima',
        'is_aktif',
        'nama_ayah',
        'pekerjaan_ayah',
        'nama_ibu',
        'pekerjaan_ibu',
        'no_hp_orang_tua',
    ];

    protected $casts = [
        'tanggal_diterima' => 'date',
        'is_aktif' => 'boolean',
    ];

    public function calonSiswa(): BelongsTo
    {
        return $this->belongsTo(CalonSiswa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function riwayatKelas()
    {
        return $this->hasMany(RiwayatKelas::class);
    }

    public function waliMurids()
    {
        return $this->hasMany(WaliMurid::class);
    }

    public function absensis()
    {
        return $this->hasMany(Absensi::class);
    }
}
