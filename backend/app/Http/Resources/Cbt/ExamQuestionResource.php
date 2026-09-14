<?php

namespace App\Http\Resources\Cbt;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $options = $this->options;
        if ($this->resource->exam->shuffle_options && is_array($options)) {
            shuffle($options);
        }

        return [
            'id' => $this->id,
            'question_text' => $this->question_text,
            'question_image' => $this->question_image,
            'type' => $this->type,
            'options' => $options,
            'score' => $this->score,
            // 'correct_answer' SENGAJA TIDAK DIMASUKKAN — jangan pernah tambahkan ini.
            // Ini satu-satunya jalan soal keluar ke API CBT; jangan return model mentah.
        ];
    }
}
