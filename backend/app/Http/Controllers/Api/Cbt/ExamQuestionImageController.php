<?php

namespace App\Http\Controllers\Api\Cbt;

use App\Http\Controllers\Controller;
use App\Models\ExamQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyaji gambar soal untuk siswa yang sedang ujian.
 *
 * Gambar tidak lagi hidup di disk `public` — isinya bank soal ujian, dan URL
 * `/storage/...` bisa diambil tanpa login. Alurnya memakai
 * ExamSessionController::ownsOngoingExam() sehingga hanya siswa dengan sesi
 * ongoing pada ujian ITU yang bisa mengambil gambarnya.
 *
 * Soal bank (`exam_id` null) selalu 404 di sini: bank soal adalah milik guru,
 * bukan bahan ujian.
 */
class ExamQuestionImageController extends Controller
{
    public function __invoke(Request $request, ExamQuestion $question): StreamedResponse
    {
        abort_if(! $question->question_image, 404);

        // Soal bank tidak pernah disajikan ke siswa.
        abort_if($question->exam_id === null, 404);

        abort_unless(ExamSessionController::ownsOngoingExam($request, $question->exam_id), 404);

        abort_unless($question->imageExists(), 404);

        return Storage::disk(ExamQuestion::DISK)->response($question->question_image);
    }
}
