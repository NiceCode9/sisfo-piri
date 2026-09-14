<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestion extends Model
{
    protected $fillable = [
        'exam_id', 'question_text', 'question_image', 'type', 'options', 'correct_answer', 'score', 'order',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
