<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Controller;
use App\Models\ExamQuestion;
use App\Models\QuestionBank;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request, QuestionBank $bank): RedirectResponse
    {
        $this->authorize('update', $bank);

        $data = $request->validate([
            'question_text' => ['required', 'string'],
            'question_image' => ['nullable', 'image', 'max:2048'],
            'type' => ['required', 'in:single_choice,multiple_choice,essay'],
            'options' => ['nullable', 'array'],
            'options.*.key' => ['required_with:options', 'string', 'max:2'],
            'options.*.text' => ['required_with:options', 'string'],
            'correct_answer' => ['nullable', 'array'],
            'score' => ['required', 'integer', 'min:1'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

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
        if ($bank !== null) {
            $this->authorize('update', $bank);
        }
        if ($question->question_image) {
            Storage::disk('public')->delete($question->question_image);
        }
        $question->delete();

        return back()->with('success', 'Soal dihapus dari bank.');
    }
}
