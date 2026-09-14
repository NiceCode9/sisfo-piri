<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = [
        'rombel_id', 'name', 'description', 'duration_minutes', 'available_from', 'available_until',
        'max_violation_count', 'shuffle_questions', 'shuffle_options', 'status', 'created_by',
    ];

    protected $casts = [
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
    ];

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(ExamToken::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ExamParticipant::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }
}
