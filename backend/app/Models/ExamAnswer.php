<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAnswer extends Model
{
    protected $fillable = [
        'exam_session_id', 'exam_question_id', 'answer', 'is_correct', 'score_obtained', 'answered_at',
    ];

    protected $casts = [
        'answer' => 'array',
        'answered_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'exam_question_id');
    }
}
