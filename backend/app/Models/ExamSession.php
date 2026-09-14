<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    protected $fillable = [
        'exam_id', 'exam_token_id', 'user_id', 'started_at', 'expected_end_at', 'finished_at',
        'last_heartbeat_at', 'status', 'finish_reason', 'violation_count', 'score', 'client_ip', 'user_agent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expected_end_at' => 'datetime',
        'finished_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(ExamToken::class, 'exam_token_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(ExamViolation::class);
    }
}
