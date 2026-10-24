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

        // Pengacakan dibuat stabil per sesi (seed = exam_session_id) supaya
        // refresh/resume tidak mengubah urutan soal dan pilihan, tetapi tetap
        // berbeda antar siswa.
        if ($session->exam->shuffle_questions) {
            $questions = collect($this->acakUrutan($questions->values()->all(), $session->id));
        }

        if ($session->exam->shuffle_options) {
            $questions = $questions->map(function ($q) use ($session) {
                if (is_array($q->options)) {
                    $clone = clone $q;
                    $clone->options = $this->acakUrutan($q->options, $session->id + (int) $q->id);

                    return $clone;
                }

                return $q;
            });
        }

        $savedAnswers = $session->answers()->pluck('answer', 'exam_question_id');

        return response()->json([
            'questions' => ExamQuestionResource::collection($questions),
            'saved_answers' => $savedAnswers,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Fisher-Yates dengan PRNG deterministik (LCG) supaya urutan stabil untuk
     * seed yang sama, tetapi berbeda antar sesi siswa.
     *
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    private function acakUrutan(array $items, int $seed): array
    {
        $n = count($items);
        if ($n < 2) {
            return array_values($items);
        }

        $arr = array_values($items);
        $state = $seed === 0 ? 1 : abs($seed);

        for ($i = $n - 1; $i > 0; $i--) {
            $state = ($state * 1103515245 + 12345) & 0x7FFFFFFF;
            $j = $state % ($i + 1);
            [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
        }

        return $arr;
    }
}
