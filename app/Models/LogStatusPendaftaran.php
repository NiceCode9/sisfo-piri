<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogStatusPendaftaran extends Model
{
    use HasFactory;

    protected $fillable = [
        'calon_siswa_id',
        'status_sebelumnya',
        'status_baru',
        'user_id',
        'catatan'
    ];

    public function calonSiswa()
    {
        return $this->belongsTo(CalonSiswa::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
