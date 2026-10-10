<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Controller;
use App\Models\ExamAnswer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ExamAnswerController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [new Middleware('permission:cbt.manage')];
    }

    public function update(Request $request, ExamAnswer $answer): RedirectResponse
    {
        $answer->loadMissing('question');

        abort_if($answer->session === null || $answer->session->exam === null, 404);

        // Koreksi nilai soal ujian orang lain harus ditolak: pemeriksa harus
        // memegang ujian tempat jawaban ini berada.
        $this->authorize('update', $answer->session->exam);

        $question = $answer->question;
        abort_if($question === null, 404);

        $data = $request->validate([
            // Batas atas ikut bobot soal, bukan angka tetap 100 — soal
            // 5 poin tidak boleh diberi nilai 100.
            'score_obtained' => ['required', 'numeric', 'min:0', 'max:'.$question->score],
        ]);

        $answer->update([
            'score_obtained' => $data['score_obtained'],
            'is_correct' => $data['score_obtained'] > 0,
        ]);

        // re-hitung skor sesi
        $session = $answer->session;
        $total = $session->answers()->sum('score_obtained');
        $session->update(['score' => $total]);

        return back()->with('success', 'Nilai diperbarui.');
    }
}
