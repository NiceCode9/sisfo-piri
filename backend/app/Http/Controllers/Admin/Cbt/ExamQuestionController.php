<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cbt\StoreExamQuestionRequest;
use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;

class ExamQuestionController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [new Middleware('permission:cbt.manage')];
    }

    public function store(StoreExamQuestionRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $data = $request->validatedData();
        $data['exam_id'] = $exam->id;

        if ($request->hasFile('question_image')) {
            $data['question_image'] = $request->file('question_image')->store('cbt/questions', 'public');
        }

        ExamQuestion::create($data);
        Cache::forget("exam:{$exam->id}:questions");

        return back()->with('success', 'Soal ditambahkan.');
    }

    public function destroy(ExamQuestion $question): RedirectResponse
    {
        // Route ini khusus soal ujian. Soal bank punya exam_id NULL, jadi harus
        // ditolak — bukan lolos karena "tidak punya exam".
        abort_if($question->exam_id === null, 404);

        $this->authorize('update', $question->exam);

        $examId = $question->exam_id;
        $question->delete();
        Cache::forget("exam:{$examId}:questions");

        return back()->with('success', 'Soal dihapus.');
    }
}
