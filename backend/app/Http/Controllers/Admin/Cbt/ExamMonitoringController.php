<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Api\Cbt\ExamSessionController;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamViolation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamMonitoringController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [new Middleware('permission:cbt.view')];
    }

    public function index(Exam $exam): View
    {
        $this->authorize('view', $exam);

        $exam->loadCount(['questions', 'tokens']);

        return view('admin.cbt.monitoring.index', compact('exam'));
    }

    public function data(Exam $exam): JsonResponse
    {
        $this->authorize('view', $exam);

        $sessions = ExamSession::with('user')
            ->withCount('answers')
            ->where('exam_id', $exam->id)
            ->latest()
            ->get()
            ->map(function ($s) {
                $remaining = $s->expected_end_at ? now()->diffInSeconds($s->expected_end_at, false) : null;
                $isOnline = $s->last_heartbeat_at && $s->last_heartbeat_at->gt(now()->subSeconds(30));

                return [
                    'id' => $s->id,
                    'student_name' => $s->user->name ?? '-',
                    'status' => $s->status,
                    'remaining_seconds' => $remaining,
                    'violation_count' => $s->violation_count,
                    'is_online' => $isOnline,
                    // Diambil lewat withCount, bukan $s->answers()->count()
                    // per sesi. Halaman ini dipoll berkala; pada kelas 40
                    // siswa itu berarti 40 query sia-sia tiap beberapa detik.
                    'answered_count' => $s->answers_count,
                    'score' => $s->score,
                ];
            });

        return response()->json([
            'sessions' => $sessions,
            'total_questions' => $exam->questions()->count(),
            'summary' => [
                'ongoing' => $sessions->where('status', 'ongoing')->count(),
                'finished' => $sessions->where('status', 'finished')->count(),
                'disqualified' => $sessions->where('status', 'disqualified')->count(),
                // `expired` ditulis sweeper cbt:expire-sessions untuk sesi yang
                // ditinggalkan siswa. Sebelumnya status ini tidak pernah diisi
                // sehingga baris tersebut tetap terlihat sebagai ujian berjalan.
                'expired' => $sessions->where('status', 'expired')->count(),
            ],
        ]);
    }

    public function force(Exam $exam, ExamSession $session): RedirectResponse
    {
        abort_unless($session->exam_id === $exam->id, 404);

        // Force adalah tindakan yang mengubah nilai siswa, jadi butuh izin Manage
        // meski halaman monitoringnya sendiri hanya butuh View.
        $this->authorize('update', $exam);

        $lock = Cache::lock("force:{$session->id}", 10);

        if (! $lock->get()) {
            return back()->with('error', 'Sesi sedang diproses.');
        }

        try {
            app(ExamSessionController::class)->finalizeSession($session, 'admin_force');
        } finally {
            $lock->release();
        }

        return back()->with('success', 'Sesi dihentikan (admin_force).');
    }

    public function violationsCsv(Exam $exam): StreamedResponse
    {
        $this->authorize('view', $exam);

        $violations = ExamViolation::with('session.user')
            ->whereHas('session', fn ($q) => $q->where('exam_id', $exam->id))
            ->latest()->get();

        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"violations-{$exam->id}.csv\""];

        return response()->stream(function () use ($violations) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['exam_session_id', 'user', 'type', 'occurred_at', 'meta']);
            foreach ($violations as $v) {
                fputcsv($out, [$v->exam_session_id, $v->session->user->name ?? '-', $v->type, $v->occurred_at, json_encode($v->meta)]);
            }
            fclose($out);
        }, 200, $headers);
    }
}
