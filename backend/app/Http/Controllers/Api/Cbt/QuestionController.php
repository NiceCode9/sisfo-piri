<?php

namespace App\Http\Controllers\Api\Cbt;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cbt\ExamQuestionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['exam_session_id' => 'required|integer']);
        $session = ExamSessionController::resolveOngoingSession($request, $data['exam_session_id']);

        // Cache soal per exam_id — base soal sama untuk semua siswa dalam satu exam,
        // tidak perlu query DB tiap ada yang join. Durasi cache sesuaikan masa aktif token.
        // PENTING: di controller admin (tempat guru edit soal), tambahkan
        // Cache::forget("exam:{$exam->id}:questions") setiap kali soal diubah,
        // supaya siswa tidak dapat soal versi lama.
        $questions = Cache::remember(
            "exam:{$session->exam_id}:questions",
            now()->addHours(6),
            fn () => $session->exam->questions()->orderBy('order')->get()
        );

        if ($session->exam->shuffle_questions) {
            $questions = $questions->shuffle(); // shuffle per-request, hasil cache tidak ke-shuffle permanen
        }

        $savedAnswers = $session->answers()->pluck('answer', 'exam_question_id');

        return response()->json([
            'questions' => ExamQuestionResource::collection($questions),
            'saved_answers' => $savedAnswers,
        ]);
    }
}
