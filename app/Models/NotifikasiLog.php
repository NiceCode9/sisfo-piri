<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotifikasiLog extends Model
{
    protected $fillable = [
        'tipe',
        'tujuan',
        'pesan',
        'status',
        'respons',
    ];
}
