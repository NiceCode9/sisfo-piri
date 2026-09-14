<?php

namespace App\Http\Controllers\Api\Cbt;

use App\Http\Controllers\Controller;
use App\Models\ExamAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AnswerController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'exam_session_id' => 'required|integer',
            'exam_question_id' => 'required|integer',
            'answer' => 'required', // array utk pilihan ganda, string/objek utk essay
        ]);

        $session = ExamSessionController::resolveOngoingSession($request, $data['exam_session_id']);

        // Pastikan soal ini memang bagian dari exam milik sesi ini
        $questionBelongs = $session->exam->questions()->where('id', $data['exam_question_id'])->exists();
        if (! $questionBelongs) {
            abort(422, 'Soal tidak valid untuk sesi ujian ini.');
        }

        $answer = ExamAnswer::updateOrCreate(
            [
                'exam_session_id' => $session->id,
                'exam_question_id' => $data['exam_question_id'],
            ],
            [
                'answer' => is_array($data['answer']) ? $data['answer'] : [$data['answer']],
                'answered_at' => Carbon::now(),
            ]
        );

        return response()->json(['ok' => true, 'saved_at' => $answer->answered_at->toIso8601String()]);
    }
}
