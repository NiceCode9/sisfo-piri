<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Rombel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ExamController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:cbt.view', only: ['index', 'show']),
            new Middleware('permission:cbt.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index(): View
    {
        $exams = Exam::with(['rombel.kelas', 'tokens'])->latest()->paginate(10);

        return view('admin.cbt.exams.index', compact('exams'));
    }

    public function create(): View
    {
        $rombels = Rombel::with(['kelas', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id')->get();

        return view('admin.cbt.exams.create', compact('rombels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rombel_id' => ['required', 'exists:rombels,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:300'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after:available_from'],
            'max_violation_count' => ['required', 'integer', 'min:1', 'max:100'],
            'shuffle_questions' => ['sometimes', 'boolean'],
            'shuffle_options' => ['sometimes', 'boolean'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $data['created_by'] = auth()->id();
        $data['shuffle_questions'] = $request->boolean('shuffle_questions', true);
        $data['shuffle_options'] = $request->boolean('shuffle_options', true);

        $exam = Exam::create($data);

        return redirect()->route('admin.cbt.exams.index')->with('success', "Ujian {$exam->name} dibuat.");
    }

    public function show(Exam $exam): View
    {
        $exam->load(['rombel.kelas', 'questions', 'tokens', 'participants', 'sessions']);

        return view('admin.cbt.exams.show', compact('exam'));
    }

    public function edit(Exam $exam): View
    {
        $rombels = Rombel::with(['kelas', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id')->get();

        return view('admin.cbt.exams.edit', compact('exam', 'rombels'));
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $data = $request->validate([
            'rombel_id' => ['required', 'exists:rombels,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:300'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after:available_from'],
            'max_violation_count' => ['required', 'integer', 'min:1', 'max:100'],
            'shuffle_questions' => ['sometimes', 'boolean'],
            'shuffle_options' => ['sometimes', 'boolean'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['shuffle_options'] = $request->boolean('shuffle_options');

        $exam->update($data);
        Cache::forget("exam:{$exam->id}:questions");

        return redirect()->route('admin.cbt.exams.index')->with('success', 'Ujian diperbarui.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $exam->delete();

        return redirect()->route('admin.cbt.exams.index')->with('success', 'Ujian dihapus.');
    }
}
