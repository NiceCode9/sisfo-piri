<?php

namespace App\Http\Controllers\Api\Cbt;

use App\Http\Controllers\Controller;
use App\Jobs\RecordHeartbeat;
use App\Models\ExamQuestion;
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
        // Diurutkan eksplisit: unaiqueness-nya per token, jadi seorang siswa
        // bisa punya lebih dari satu sesi ongoing. Tanpa orderBy, `first()`
        // mengembalikan baris sembarang dan frontend melanjutkan ujian yang
        // kebetulan terambil — bukan yang sedang dikerjakan siswa.
        $session = ExamSession::with('exam')
            ->where('user_id', $request->user()->id)
            ->where('status', 'ongoing')
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();

        if (! $session) {
            return response()->json(['active' => false]);
        }

        // Sesi yang waktunya sudah lewat tapi belum disentuh sweeper tidak
        // boleh ditawarkan sebagai ujian aktif — frontend akan membuka ujian
        // yang sudah tutup.
        if (Carbon::now()->gt($session->expected_end_at->copy()->addSeconds(5))) {
            $this->finalizeSession($session, 'time_up');

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
            'violation_count' => $session->violation_count,
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

        // Jendela ujian yang diatur guru. Kolom available_from/available_until
        // sebelumnya bisa diisi dari form tetapi tidak pernah dibaca di mana
        // pun, sehingga ujian tetap bisa dimasuki di luar jadwalnya selama
        // token masih aktif.
        $exam = $examToken->exam;

        if ($exam->available_from !== null && $now->lt($exam->available_from)) {
            throw ValidationException::withMessages(['token' => ['Ujian belum dimulai.']]);
        }

        if ($exam->available_until !== null && $now->gt($exam->available_until)) {
            throw ValidationException::withMessages(['token' => ['Ujian sudah berakhir.']]);
        }

        if ($exam->status !== 'published') {
            throw ValidationException::withMessages(['token' => ['Ujian belum dipublikasikan.']]);
        }

        // Cek rombel: siswa harus berada di rombel ujian
        $siswa = Siswa::where('user_id', $user->id)->first();
        if ($siswa && $exam->rombel_id) {
            $rombel = Rombel::find($exam->rombel_id);
            if ($rombel && ($siswa->kelas_id !== $rombel->kelas_id || $siswa->tahun_ajaran_id !== $rombel->tahun_ajaran_id)) {
                throw ValidationException::withMessages(['token' => ['Anda tidak terdaftar di rombel ujian ini.']]);
            }
        }

        // Cek whitelist peserta kalau ada
        $hasWhitelist = $exam->participants()->exists();
        if ($hasWhitelist) {
            $isAllowed = $exam->participants()->where('user_id', $user->id)->exists();
            if (! $isAllowed) {
                throw ValidationException::withMessages(['token' => ['Anda tidak terdaftar sebagai peserta ujian ini.']]);
            }
        }

        // Transaksi: cegah race condition kalau siswa klik join dobel-dobel cepat
        $session = DB::transaction(function () use ($examToken, $user, $now, $request) {
            // Dicari per UJIAN, bukan per token. Sebelumnya kuerinya
            // `where(exam_token_id)` sehingga "sudah menyelesaikan ujian ini"
            // hanya berlaku untuk token itu saja — token berikutnya untuk
            // ujian yang sama membuka akses ulang. Remedial yang sah dibuat
            // lewat baris `exams` baru berpenanda, bukan lewat token baru.
            $existing = ExamSession::where('exam_id', $examToken->exam_id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if (in_array($existing->status, ['finished', 'expired', 'disqualified'])) {
                    throw ValidationException::withMessages(['token' => ['Anda sudah menyelesaikan ujian ini sebelumnya.']]);
                }

                // resume sesi yang sudah ada — termasuk lewat token berbeda.
                // Timer tetap milik sesi asalnya, jadi siswa tidak kehilangan
                // waktu hanya karena salah memilih token.
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
            // `admin_force` sengaja tidak boleh dikirim siswa: itu tindakan
            // moderator yang hanya bisa berasal dari ExamMonitoringController.
            // Jika ikut diterima, siswa bisa menyamar aksi pengawas di audit.
            'finish_reason' => 'required|in:manual,time_up,violation_limit',
        ]);

        $session = $this->ownedOngoingSession($request, $data['exam_session_id']);

        $this->finalizeSession($session, $data['finish_reason']);

        // `score` sengaja tidak dikembalikan. Skor per-soal tersimpan, jadi
        // mengembalikan total di sini memberi siswa kunci jawaban: coba satu
        // opsi, finish, baca selisih skor, lanjutkan. Nilai tetap dibaca lewat
        // portal sekolah seperti Finished.tsx/Controler lain.
        return response()->json([
            'ok' => true,
            'status' => $session->fresh()->status,
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

    /**
     * @param  string|null  $status  Status akhir eksplisit. Sweeper memakai
     *                               'expired' untuk menandai sesi yang ditinggalkan
     *                               siswa, sehingga baris itu tidak lagi ikut
     *                               terhitung sebagai ujian berjalan.
     */
    public function finalizeSession(ExamSession $session, string $reason, ?string $status = null): void
    {
        DB::transaction(function () use ($session, $reason, $status) {
            $session->refresh();
            if ($session->status !== 'ongoing') {
                return; // sudah difinalisasi request lain, hindari double-processing
            }

            $score = $this->calculateScore($session);

            $session->update([
                'status' => $status ?? ($reason === 'violation_limit' ? 'disqualified' : 'finished'),
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

            if ($question === null) {
                continue; // soal dihapus guru setelah siswa menjawab
            }

            if ($question->type === 'essay') {
                continue; // dikoreksi manual oleh guru nanti
            }

            $obtained = $this->skorUntuk($answer->answer, $question);
            $isCorrect = $obtained > 0;

            $answer->update([
                'is_correct' => $isCorrect,
                'score_obtained' => $obtained,
            ]);

            $totalScore += $obtained;
        }

        return $totalScore;
    }

    /**
     * Pilihan tunggal: benar bila himpunan kunci sama persis.
     *
     * Pilihan jamak: kompensasi parsialproporsional. Tanpa ini, siswa yang
     * benar memilih 3 dari 4 opsi benar tetap dapat 0, padahal kelonggaran
     * separuh lebih wajar daripada nol. Skor dibatasi di atas
     * question->score supaya tebakan overflow tidak menambah nilai.
     */
    private function skorUntuk(?array $given, ExamQuestion $question): float
    {
        $weight = (float) $question->score;

        $answer = $this->normalizeKeys($question->correct_answer);
        $given = $this->normalizeKeys($given);

        if ($answer === []) {
            return 0.0; // kunci kosong = soal tidak mungkin dijawab
        }

        $benar = count(array_intersect($given, $answer));

        if ($question->type === 'single_choice') {
            return $benar === 1 && count($given) === 1 ? $weight : 0.0;
        }

        // Seberapa besar porsi jawaban siswa berada di dalam kunci. Menjawab
        // semua opsi benar harus tetap bernilai 0.
        $rasio = $benar / max(count($answer), count($given));

        return round($weight * $rasio, 2);
    }

    /**
     * Kunci opsi datang dari DB sebagai string, sedangkan payload JSON siswa
     * bisa berupa integer atau float. Tanpa normalisasi, `["1"]` dan `[1]`
     * tidak pernah dianggap sama meski maksudnya sama.
     *
     * @param  array<int, mixed>|null  $keys
     * @return list<string>
     */
    private function normalizeKeys(?array $keys): array
    {
        if ($keys === null) {
            return [];
        }

        return array_values(array_unique(array_map(
            fn ($k) => (string) $k,
            array_filter($keys, fn ($k) => $k !== null && $k !== '')
        )));
    }
}
