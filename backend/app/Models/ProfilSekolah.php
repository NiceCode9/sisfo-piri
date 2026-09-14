<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfilSekolah extends Model
{
    protected $fillable = [
        'nama_sekolah',
        'npsn',
        'alamat',
        'telp',
        'email',
        'website',
        'tahun_berdiri',
        'akreditasi',
        'logo_path',
        'foto_gedung_path',
        'sambutan',
        'visi',
        'misi',
        'nama_kepala',
        'foto_kepala_path',
        'maps_embed_url',
    ];

    protected $casts = [
        'misi' => 'array',
        'tahun_berdiri' => 'integer',
    ];

    /**
     * Ambil baris profil tunggal (singleton).
     */
    public static function aktif(): ?self
    {
        return static::first();
    }
}
