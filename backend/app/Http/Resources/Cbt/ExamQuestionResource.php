<?php

namespace App\Http\Resources\Cbt;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Pengacakan opsi kini dilakukan di QuestionController secara stabil per
        // sesi, sehingga resource tidak boleh mengacak ulang (yang akan membuat
        // urutan berubah tiap serialisasi).
        $options = $this->options;

        return [
            'id' => $this->id,
            'question_text' => $this->question_text,
            // URL route terproteksi, BUKAN path storage mentah. Path mentah
            // dua masalah sekaligus: membocorkan struktur disk, dan
            // dipakai SPA sebagai `src` sehingga resolve ke /cbt/questions/...
            // yang 404 (URL publik ada di bawah /storage).
            //
            // Pakai path relatif (bukan absolute dari route()) supaya tidak
            // bergantung pada APP_URL yang sering salah di production. Frontend
            // menggabungkannya dengan VITE_API_BASE_URL yang sudah benar.
            'question_image' => $this->question_image
                ? '/exam/question-image/' . $this->id
                : null,
            'type' => $this->type,
            'options' => $options,
            'score' => $this->score,
            // 'correct_answer' SENGAJA TIDAK DIMASUKKAN — jangan pernah tambahkan ini.
            // Ini satu-satunya jalan soal keluar ke API CBT; jangan return model mentah.
        ];
    }
}
