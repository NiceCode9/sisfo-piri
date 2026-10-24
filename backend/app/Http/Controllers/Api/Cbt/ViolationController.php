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
            'client_request_id' => ['nullable', 'string', 'max:64'],
            // meta disimpan sebagai JSON; tanpa batas ukuran, satu permintaan
            // bisa menulis blob besar ke tabel exam_violations.
            'meta' => ['nullable', 'array', 'max:10'],
            'meta.*' => ['nullable'],
        ]);

        $session = ExamSessionController::resolveOngoingSession($request, $data['exam_session_id']);

        // Idempotency: retry dengan client_request_id yang sama tidak boleh
        // menambah violation_count dua kali.
        if (! empty($data['client_request_id'])) {
            $cacheKey = "cbt:violation:{$session->id}:{$data['client_request_id']}";
            if (cache()->has($cacheKey)) {
                $cached = cache()->get($cacheKey);

                return response()->json($cached);
            }
        }

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

        $payload = [
            'ok' => true,
            'violation_count' => $session->violation_count,
            'max_violation_count' => $session->exam->max_violation_count,
            'disqualified' => $shouldDisqualify,
        ];

        if (! empty($data['client_request_id'])) {
            cache()->put("cbt:violation:{$session->id}:{$data['client_request_id']}", $payload, now()->addHours(6));
        }

        return response()->json($payload);
    }
}
