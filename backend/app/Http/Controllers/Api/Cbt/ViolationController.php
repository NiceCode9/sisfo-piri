<?php

namespace App\Http\Controllers\Api\Cbt;

use App\Http\Controllers\Controller;
use App\Jobs\LogViolationDetail;
use Illuminate\Http\Request;

class ViolationController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'exam_session_id' => 'required|integer',
            'type' => 'required|in:fullscreen_exit,tab_blur,visibility_hidden,devtools_suspected,copy_paste_attempt,connection_lost',
            'meta' => 'nullable|array',
        ]);

        $session = ExamSessionController::resolveOngoingSession($request, $data['exam_session_id']);

        // connection_lost murni jaringan — catat audit tapi jangan hitung batas curang
        $isCheatingViolation = $data['type'] !== 'connection_lost';

        if ($isCheatingViolation) {
            $session->increment('violation_count');
            $session->refresh();
        }

        LogViolationDetail::dispatch($session->id, $data['type'], $data['meta'] ?? null);

        $shouldDisqualify = $isCheatingViolation && $session->violation_count >= $session->exam->max_violation_count;
        if ($shouldDisqualify) {
            app(ExamSessionController::class)->finalizeSession($session, 'violation_limit');
        }

        return response()->json([
            'ok' => true,
            'violation_count' => $session->violation_count,
            'max_violation_count' => $session->exam->max_violation_count,
            'disqualified' => $shouldDisqualify,
        ]);
    }
}
