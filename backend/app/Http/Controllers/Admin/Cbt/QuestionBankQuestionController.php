<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cbt\StoreBankQuestionRequest;
use App\Models\ExamQuestion;
use App\Models\QuestionBank;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class QuestionBankQuestionController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [new Middleware('permission:cbt.manage')];
    }

    public function store(StoreBankQuestionRequest $request, QuestionBank $bank): RedirectResponse
    {
        $this->authorize('update', $bank);

        $data = $request->validatedData();

        $imagePath = null;
        if ($request->hasFile('question_image')) {
            $imagePath = $request->file('question_image')->store('cbt/questions', 'public');
        }

        ExamQuestion::create([
            'exam_id' => null,
            'question_bank_id' => $bank->id,
            'question_text' => $data['question_text'],
            'question_image' => $imagePath,
            'type' => $data['type'],
            'options' => $data['options'] ?? null,
            'correct_answer' => $data['correct_answer'] ?? null,
            'score' => $data['score'],
            'order' => $data['order'] ?? 0,
        ]);

        return back()->with('success', 'Soal ditambahkan ke bank.');
    }

    public function destroy(ExamQuestion $question): RedirectResponse
    {
        $bank = $question->bank;

        // Fail closed. Versi lama memakai `if ($bank !== null)` sehingga soal
        // ujian (question_bank_id NULL) mencapai delete() tanpa satu pun
        // authorize() — siapa pun pemegang cbt.manage bisa menghapus soal
        // ujian milik guru lain lewat route bank.
        abort_if($bank === null, 404);

        $this->authorize('update', $bank);

        if ($question->question_image) {
            Storage::disk('public')->delete($question->question_image);
        }
        $question->delete();

        return back()->with('success', 'Soal dihapus dari bank.');
    }
}
