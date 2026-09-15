<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Controller;
use App\Models\ExamAnswer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ExamAnswerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('permission:cbt.manage')];
    }

    public function update(Request $request, ExamAnswer $answer): RedirectResponse
    {
        $data = $request->validate([
            'score_obtained' => ['required', 'numeric', 'min:0', 'max:100'],
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
