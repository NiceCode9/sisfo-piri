<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\QuestionBank;
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

    public function index(Request $request): View
    {
        $query = Exam::with(['rombel.kelas', 'mataPelajaran', 'tokens'])->latest();

        if (! $request->user()->hasRole(['super-admin', 'admin'])) {
            $guru = Guru::where('user_id', $request->user()->id)->first();
            if ($guru) {
                $mapelIds = Pengampu::where('guru_id', $guru->id)->pluck('mata_pelajaran_id')->unique();
                $rombelIds = Pengampu::where('guru_id', $guru->id)->pluck('rombel_id')->unique();
                $query->whereIn('rombel_id', $rombelIds)->whereIn('mata_pelajaran_id', $mapelIds);
            } else {
                $query->whereRaw('1=0');
            }
        }

        $exams = $query->paginate(10)->withQueryString();

        return view('admin.cbt.exams.index', compact('exams'));
    }

    public function create(Request $request): View
    {
        $rombels = Rombel::with(['kelas', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id')->get();
        $mapels = $this->mapelsForCurrentUser($request->user());

        return view('admin.cbt.exams.create', compact('rombels', 'mapels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rombel_id' => ['required', 'exists:rombels,id'],
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajarans,id'],
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

        $this->authorizePengampu($request->user(), (int) $data['rombel_id'], (int) $data['mata_pelajaran_id']);

        $data['created_by'] = auth()->id();
        $data['shuffle_questions'] = $request->boolean('shuffle_questions', true);
        $data['shuffle_options'] = $request->boolean('shuffle_options', true);

        $exam = Exam::create($data);

        return redirect()->route('admin.cbt.exams.index')->with('success', "Ujian {$exam->name} dibuat.");
    }

    public function show(Request $request, Exam $exam): View
    {
        $exam->load(['rombel.kelas', 'mataPelajaran', 'questions', 'tokens', 'participants', 'sessions']);
        $banks = QuestionBank::withCount('questions')
            ->where('mata_pelajaran_id', $exam->mata_pelajaran_id)
            ->when(! $request->user()->hasRole(['super-admin', 'admin']), function ($q) use ($request) {
                $guru = Guru::where('user_id', $request->user()->id)->first();
                $q->where('guru_id', $guru?->id ?? -1);
            })
            ->orderBy('nama')->get();

        return view('admin.cbt.exams.show', compact('exam', 'banks'));
    }

    public function edit(Request $request, Exam $exam): View
    {
        $rombels = Rombel::with(['kelas', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id')->get();
        $mapels = $this->mapelsForCurrentUser($request->user());

        return view('admin.cbt.exams.edit', compact('exam', 'rombels', 'mapels'));
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $data = $request->validate([
            'rombel_id' => ['required', 'exists:rombels,id'],
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajarans,id'],
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

        $this->authorizePengampu($request->user(), (int) $data['rombel_id'], (int) $data['mata_pelajaran_id']);

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

    public function importFromBank(Request $request, Exam $exam, QuestionBank $bank): RedirectResponse
    {
        if ((int) $exam->mata_pelajaran_id !== (int) $bank->mata_pelajaran_id) {
            return back()->withErrors(['bank' => 'Bank dan ujian harus mata pelajaran yang sama.']);
        }

        $data = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:exam_questions,id'],
        ]);

        $count = 0;
        foreach ($data['question_ids'] as $qid) {
            $source = ExamQuestion::where('id', $qid)->where('question_bank_id', $bank->id)->first();
            if (! $source) {
                continue;
            }
            ExamQuestion::create([
                'exam_id' => $exam->id,
                'question_bank_id' => $bank->id,
                'source_question_id' => $source->id,
                'question_text' => $source->question_text,
                'question_image' => $source->question_image,
                'type' => $source->type,
                'options' => $source->options,
                'correct_answer' => $source->correct_answer,
                'score' => $source->score,
                'order' => $source->order,
            ]);
            $count++;
        }

        Cache::forget("exam:{$exam->id}:questions");

        return back()->with('success', "{$count} soal disalin dari bank {$bank->nama}.");
    }

    private function mapelsForCurrentUser($user)
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return MataPelajaran::aktif()->orderBy('nama')->get();
        }
        $guru = Guru::where('user_id', $user->id)->first();
        if ($guru === null) {
            return collect();
        }
        $mapelIds = Pengampu::where('guru_id', $guru->id)->pluck('mata_pelajaran_id')->unique();

        return MataPelajaran::whereIn('id', $mapelIds)->aktif()->orderBy('nama')->get();
    }

    private function authorizePengampu($user, int $rombelId, int $mapelId): void
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return;
        }
        $guru = Guru::where('user_id', $user->id)->first();
        $isPengampu = $guru && Pengampu::where('guru_id', $guru->id)->where('rombel_id', $rombelId)->where('mata_pelajaran_id', $mapelId)->exists();
        abort_unless($isPengampu, 403, 'Anda tidak mengampu mapel ini di rombel tersebut.');
    }
}
