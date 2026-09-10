<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brosur extends Model
{
    protected $fillable = [
        'title',
        'desc',
        'image_path',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }
}
