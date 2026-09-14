<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamToken extends Model
{
    protected $fillable = [
        'exam_id', 'token', 'active_from', 'active_until', 'max_usage', 'used_count', 'is_active', 'created_by',
    ];

    protected $casts = [
        'active_from' => 'datetime',
        'active_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
