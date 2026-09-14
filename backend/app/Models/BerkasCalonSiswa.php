<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BerkasCalonSiswa extends Model
{
    use HasFactory;

    protected $fillable = [
        'calon_siswa_id',
        'ijazah_path',
        'kk_path',
        'akta_path',
        'foto_path',
        'skl_path',
        'krm_path',
        'kip_path',
        'catatan_berkas',
        'berkas_perlu_perbaikan',
        'alasan_penolakan',
        'status_verifikasi',
    ];

    protected $casts = [
        'berkas_perlu_perbaikan' => 'array',
    ];

    public function calonSiswa()
    {
        return $this->belongsTo(CalonSiswa::class);
    }
}
