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
            // URL route terproteksi, BUKAN path storage mentah. Path mentah
            // dua masalah sekaligus: membocorkan struktur disk, dan
            // dipakai SPA sebagai `src` sehingga resolve ke /cbt/questions/...
            // yang 404 (URL publik ada di bawah /storage).
            'question_image' => $this->question_image
                ? route('api.cbt.exam.question-image', ['question' => $this->id])
                : null,
            'type' => $this->type,
            'options' => $options,
            'score' => $this->score,
            // 'correct_answer' SENGAJA TIDAK DIMASUKKAN — jangan pernah tambahkan ini.
            // Ini satu-satunya jalan soal keluar ke API CBT; jangan return model mentah.
        ];
    }
}
