<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;

class ExamQuestionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('permission:cbt.manage')];
    }

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $data = $request->validate([
            'question_text' => ['required', 'string'],
            'type' => ['required', 'in:single_choice,multiple_choice,essay'],
            'options' => ['nullable', 'array'],
            'options.*.key' => ['required_with:options', 'string', 'max:2'],
            'options.*.text' => ['required_with:options', 'string'],
            'correct_answer' => ['nullable', 'array'],
            'score' => ['required', 'integer', 'min:1'],
            'order' => ['nullable', 'integer'],
        ]);

        $data['exam_id'] = $exam->id;
        ExamQuestion::create($data);
        Cache::forget("exam:{$exam->id}:questions");

        return back()->with('success', 'Soal ditambahkan.');
    }

    public function destroy(ExamQuestion $question): RedirectResponse
    {
        $examId = $question->exam_id;
        $question->delete();
        Cache::forget("exam:{$examId}:questions");

        return back()->with('success', 'Soal dihapus.');
    }
}
