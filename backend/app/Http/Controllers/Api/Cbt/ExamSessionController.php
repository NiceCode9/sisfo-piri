<?php

namespace App\Http\Controllers\Api\Cbt;

use App\Http\Controllers\Controller;
use App\Jobs\RecordHeartbeat;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Rombel;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamSessionController extends Controller
{
    public function active(Request $request)
    {
        $session = ExamSession::with('exam')
            ->where('user_id', $request->user()->id)
            ->where('status', 'ongoing')
            ->first();

        if (! $session) {
            return response()->json(['active' => false]);
        }

        return response()->json([
            'active' => true,
            'exam_session_id' => $session->id,
            'exam' => [
                'id' => $session->exam->id,
                'name' => $session->exam->name,
                'max_violation_count' => $session->exam->max_violation_count,
            ],
            'expected_end_at' => $session->expected_end_at->toIso8601String(),
        ]);
    }

    public function join(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
        ]);

        $user = $request->user();

        $examToken = ExamToken::with('exam')
            ->where('token', $data['token'])
            ->where('is_active', true)
            ->first();

        if (! $examToken) {
            throw ValidationException::withMessages(['token' => ['Token tidak ditemukan atau tidak aktif.']]);
        }

        $now = Carbon::now();

        if ($now->lt($examToken->active_from) || $now->gt($examToken->active_until)) {
            throw ValidationException::withMessages(['token' => ['Token sudah tidak berlaku untuk waktu ini.']]);
        }

        if ($examToken->exam->status !== 'published') {
            throw ValidationException::withMessages(['token' => ['Ujian belum dipublikasikan.']]);
        }

        // Cek rombel: siswa harus berada di rombel ujian
        $siswa = Siswa::where('user_id', $user->id)->first();
        if ($siswa && $examToken->exam->rombel_id) {
            $rombel = Rombel::find($examToken->exam->rombel_id);
            if ($rombel && ($siswa->kelas_id !== $rombel->kelas_id || $siswa->tahun_ajaran_id !== $rombel->tahun_ajaran_id)) {
                throw ValidationException::withMessages(['token' => ['Anda tidak terdaftar di rombel ujian ini.']]);
            }
        }

        // Cek whitelist peserta kalau ada
        $hasWhitelist = $examToken->exam->participants()->exists();
        if ($hasWhitelist) {
            $isAllowed = $examToken->exam->participants()->where('user_id', $user->id)->exists();
            if (! $isAllowed) {
                throw ValidationException::withMessages(['token' => ['Anda tidak terdaftar sebagai peserta ujian ini.']]);
            }
        }

        // Transaksi: cegah race condition kalau siswa klik join dobel-dobel cepat
        $session = DB::transaction(function () use ($examToken, $user, $now, $request) {
            $existing = ExamSession::where('exam_token_id', $examToken->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if (in_array($existing->status, ['finished', 'expired', 'disqualified'])) {
                    throw ValidationException::withMessages(['token' => ['Anda sudah menyelesaikan ujian ini sebelumnya.']]);
                }

                // resume sesi yang sudah ada
                return $existing;
            }

            // Cek kuota token
            if ($examToken->max_usage !== null && $examToken->used_count >= $examToken->max_usage) {
                throw ValidationException::withMessages(['token' => ['Kuota penggunaan token sudah habis.']]);
            }

            $expectedEnd = $now->copy()->addMinutes($examToken->exam->duration_minutes);

            $session = ExamSession::create([
                'exam_id' => $examToken->exam_id,
                'exam_token_id' => $examToken->id,
                'user_id' => $user->id,
                'started_at' => $now,
                'expected_end_at' => $expectedEnd,
                'status' => 'ongoing',
                'client_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $examToken->increment('used_count');

            return $session;
        });

        return response()->json([
            'exam_session_id' => $session->id,
            'exam' => [
                'id' => $session->exam->id,
                'name' => $session->exam->name,
                'max_violation_count' => $session->exam->max_violation_count,
            ],
            'started_at' => $session->started_at->toIso8601String(),
            'expected_end_at' => $session->expected_end_at->toIso8601String(),
            'violation_count' => $session->violation_count,
        ]);
    }

    public function heartbeat(Request $request)
    {
        $data = $request->validate([
            'exam_session_id' => 'required|integer',
        ]);

        $session = $this->ownedOngoingSession($request, $data['exam_session_id']);

        // Di-queue (async) supaya request siswa tidak menunggu DB write —
        // lihat app/Jobs/RecordHeartbeat.php. Butuh QUEUE_CONNECTION=redis + queue:work jalan.
        RecordHeartbeat::dispatch($session->id);

        $expired = Carbon::now()->gt($session->expected_end_at);

        return response()->json([
            'ok' => true,
            'server_time' => Carbon::now()->toIso8601String(),
            'expired' => $expired,
        ]);
    }

    public function finish(Request $request)
    {
        $data = $request->validate([
            'exam_session_id' => 'required|integer',
            'finish_reason' => 'required|in:manual,time_up,violation_limit,admin_force',
        ]);

        $session = $this->ownedOngoingSession($request, $data['exam_session_id']);

        $this->finalizeSession($session, $data['finish_reason']);

        return response()->json([
            'ok' => true,
            'status' => $session->fresh()->status,
            'score' => $session->fresh()->score,
        ]);
    }

    /**
     * Helper dipakai controller lain (AnswerController, ViolationController, QuestionController)
     * untuk memastikan sesi valid, milik user yang login, dan masih ongoing.
     * Server-side authoritative check: kalau waktu sudah lewat, langsung finalisasi di sini.
     */
    public static function resolveOngoingSession(Request $request, int $examSessionId): ExamSession
    {
        $session = ExamSession::where('id', $examSessionId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $session) {
            abort(404, 'Sesi ujian tidak ditemukan.');
        }

        if ($session->status !== 'ongoing') {
            abort(409, 'Sesi ujian sudah tidak aktif.');
        }

        if (Carbon::now()->gt($session->expected_end_at->copy()->addSeconds(5))) {
            app(self::class)->finalizeSession($session, 'time_up');
            abort(409, 'Waktu ujian sudah habis.');
        }

        return $session;
    }

    private function ownedOngoingSession(Request $request, int $examSessionId): ExamSession
    {
        return self::resolveOngoingSession($request, $examSessionId);
    }

    public function finalizeSession(ExamSession $session, string $reason): void
    {
        DB::transaction(function () use ($session, $reason) {
            $session->refresh();
            if ($session->status !== 'ongoing') {
                return; // sudah difinalisasi request lain, hindari double-processing
            }

            $score = $this->calculateScore($session);

            $session->update([
                'status' => $reason === 'violation_limit' ? 'disqualified' : 'finished',
                'finish_reason' => $reason,
                'finished_at' => Carbon::now(),
                'score' => $score,
            ]);
        });
    }

    private function calculateScore(ExamSession $session): float
    {
        $answers = $session->answers()->with('question')->get();

        $totalScore = 0;
        foreach ($answers as $answer) {
            $question = $answer->question;
            if ($question->type === 'essay') {
                continue; // dikoreksi manual oleh guru nanti
            }

            $isCorrect = $this->answerMatches($answer->answer, $question->correct_answer);
            $obtained = $isCorrect ? $question->score : 0;

            $answer->update([
                'is_correct' => $isCorrect,
                'score_obtained' => $obtained,
            ]);

            $totalScore += $obtained;
        }

        return $totalScore;
    }

    private function answerMatches(?array $given, ?array $correct): bool
    {
        if (! $given || ! $correct) {
            return false;
        }
        sort($given);
        sort($correct);

        return $given === $correct;
    }
}
