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

        // Increment + cek threshold tetap SYNCHRONOUS karena ini keputusan real-time
        // (disqualify atau tidak). Yang di-queue hanya pencatatan detail/audit-nya.
        $session->increment('violation_count');
        $session->refresh();

        LogViolationDetail::dispatch($session->id, $data['type'], $data['meta'] ?? null);

        $shouldDisqualify = $session->violation_count >= $session->exam->max_violation_count;
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
