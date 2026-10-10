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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

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

        // Satu-satunya sumber:jangkauan rombel dari Rombel::terjangkauUser().
        // Guru tanpa penugasan harus melihat NOL, bukan seluruh ujian.
        if (! $request->user()->hasRole(Rombel::ROLE_UNIVERSAL)) {
            $query->whereIn('rombel_id', Rombel::terjangkauUser($request->user())->select('rombels.id'));
        }

        $exams = $query->paginate(10)->withQueryString();

        return view('admin.cbt.exams.index', compact('exams'));
    }

    public function create(Request $request): View
    {
        $rombels = Rombel::with(['kelas', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id')->get();
        $mapels = $this->mapelsForCurrentUser($request->user());

        return view('admin.cbt.exams.create', [
            'rombels' => $rombels,
            'mapels' => $mapels,
            'kandidatRemedial' => $this->kandidatRemedial($request->user()),
        ]);
    }

    /**
     * Ujian yang boleh dijadikan asal remedial: milik guru yang sedang login,
     * bukan remedial itu sendiri, dan belum punya anak remedial.
     */
    private function kandidatRemedial($user): Collection
    {
        return Exam::with(['mataPelajaran', 'rombel.kelas'])
            ->where('is_remedial', false)
            ->whereNotIn('id', Exam::whereNotNull('remedial_of_id')->select('remedial_of_id'))
            ->when(! $user->hasRole(Rombel::ROLE_UNIVERSAL), function ($q) use ($user) {
                $guru = Guru::where('user_id', $user->id)->first();
                $q->whereIn('rombel_id', $guru ? Pengampu::where('guru_id', $guru->id)->select('rombel_id') : $q->newQuery()->whereRaw('1 = 0'));
            })
            ->orderBy('name')
            ->get();
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
            'is_remedial' => ['sometimes', 'boolean'],
            'remedial_of_id' => ['nullable', 'integer', 'exists:exams,id'],
        ]);

        $this->authorizePengampu($request->user(), (int) $data['rombel_id'], (int) $data['mata_pelajaran_id']);

        $data = $this->rapikanRemedial($data, $request);

        $data['created_by'] = auth()->id();
        $data['shuffle_questions'] = $request->boolean('shuffle_questions', true);
        $data['shuffle_options'] = $request->boolean('shuffle_options', true);

        $exam = Exam::create($data);

        return redirect()->route('admin.cbt.exams.index')->with('success', "Ujian {$exam->name} dibuat.");
    }

    public function show(Request $request, Exam $exam): View
    {
        $this->authorize('view', $exam);

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
        $this->authorize('update', $exam);

        $rombels = Rombel::with(['kelas', 'tahunAjaran'])->orderByDesc('tahun_ajaran_id')->get();
        $mapels = $this->mapelsForCurrentUser($request->user());

        return view('admin.cbt.exams.edit', [
            'exam' => $exam,
            'rombels' => $rombels,
            'mapels' => $mapels,
            'kandidatRemedial' => $this->kandidatRemedial($request->user())
                ->push($exam->is_remedial ? $exam->load(['mataPelajaran', 'rombel.kelas']) : null)
                ->filter()
                ->sortBy('name')
                ->values(),
        ]);
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

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
            'is_remedial' => ['sometimes', 'boolean'],
            'remedial_of_id' => ['nullable', 'integer', 'exists:exams,id'],
        ]);

        $this->authorizePengampu($request->user(), (int) $data['rombel_id'], (int) $data['mata_pelajaran_id']);

        $data = $this->rapikanRemedial($data, $request, $exam);

        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['shuffle_options'] = $request->boolean('shuffle_options');

        $exam->update($data);
        Cache::forget("exam:{$exam->id}:questions");

        return redirect()->route('admin.cbt.exams.index')->with('success', 'Ujian diperbarui.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorize('delete', $exam);

        $exam->delete();

        return redirect()->route('admin.cbt.exams.index')->with('success', 'Ujian dihapus.');
    }

    public function importFromBank(Request $request, Exam $exam, QuestionBank $bank): RedirectResponse
    {
        $this->authorize('update', $exam);

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

            // Bank soal bisa saja sudah menyimpan soal rusak dari masa lalu
            // (kunci tidak ada di opsi). Menyalin buta meneruskan kerusakan itu
            // ke ujian dan membuat semua siswa dapat 0 tanpa penjelasan.
            if (! $this->kunciJawabanSah($source)) {
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

        $dilewati = count($data['question_ids']) - $count;

        return back()->with('success', "{$count} soal disalin dari bank {$bank->nama}.")
            ->with('warning', $dilewati > 0
                ? "{$dilewati} soal dilewati karena kunci jawabannya tidak cocok dengan opsinya."
                : '');
    }

    /**
     * Kunci jawaban harus menunjuk opsi yang benar-benar ada, dan jumlahnya
     * harus sesuai tipe soal. Tanpa ini, `single_choice` ber kunci ganda atau
     * kunci yang tidak ada di opsi akan bernilai 0 untuk semua siswa.
     */
    private function kunciJawabanSah(ExamQuestion $question): bool
    {
        if ($question->type === 'essay') {
            return true;
        }

        $optionKeys = collect($question->options ?? [])
            ->pluck('key')
            ->filter()
            ->map(fn ($k) => (string) $k)
            ->all();

        $answer = array_map('strval', $question->correct_answer ?? []);

        if ($optionKeys === [] || $answer === []) {
            return false;
        }

        if (array_diff($answer, $optionKeys) !== []) {
            return false;
        }

        if ($question->type === 'single_choice' && count($answer) !== 1) {
            return false;
        }

        return ! ($question->type === 'multiple_choice' && count($answer) < 2);
    }

    /**
     * Normalisasi penanda remedial.
     *
     * Guard di sini penting: kalau `remedial_of_id` boleh menunjuk ujian
     * mapel atau rombel lain, nilai MTK bisa terpaut ke nilai IPA di rapor —
     * dan chains bercabang (remedial menunjuk remedial) akan damaging kunci
     * kelompok di RiwayatSiswa.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rapikanRemedial(array $data, Request $request, ?Exam $exam = null): array
    {
        $isRemedial = $request->boolean('is_remedial');
        $asalId = $data['remedial_of_id'] ?? null;

        $data['is_remedial'] = $isRemedial;

        if (! $isRemedial) {
            // Remedial tidak dicentang: penanda harus dibuang supaya kolom
            // tidak menyimpan sisa dari request sebelumnya.
            $data['remedial_of_id'] = null;

            return $data;
        }

        if ($asalId === null) {
            throw ValidationException::withMessages([
                'remedial_of_id' => ['Tentukan ujian asal yang di remedial-kan.'],
            ]);
        }

        if ($exam !== null && (int) $asalId === $exam->id) {
            throw ValidationException::withMessages([
                'remedial_of_id' => ['Ujian tidak bisa menjadi remedial dirinya sendiri.'],
            ]);
        }

        $asal = Exam::find($asalId);

        if ($asal === null) {
            throw ValidationException::withMessages([
                'remedial_of_id' => ['Ujian asal tidak ditemukan.'],
            ]);
        }

        // Rantai: remedial harus menunjuk ujian yang tidak itself remedial.
        if ($asal->is_remedial) {
            throw ValidationException::withMessages([
                'remedial_of_id' => ['Ujian asal harus ujian biasa, bukan remedial lain.'],
            ]);
        }

        if ((int) $asal->mata_pelajaran_id !== (int) $data['mata_pelajaran_id']
            || (int) $asal->rombel_id !== (int) $data['rombel_id']) {
            throw ValidationException::withMessages([
                'remedial_of_id' => ['Ujian asal harus rombel dan mata pelajaran yang sama.'],
            ]);
        }

        $data['remedial_of_id'] = $asal->id;

        return $data;
    }

    private function mapelsForCurrentUser($user)
    {
        if ($user->hasRole(Rombel::ROLE_UNIVERSAL)) {
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
        if ($user->hasRole(Rombel::ROLE_UNIVERSAL)) {
            return;
        }
        $guru = Guru::where('user_id', $user->id)->first();
        $isPengampu = $guru && Pengampu::where('guru_id', $guru->id)->where('rombel_id', $rombelId)->where('mata_pelajaran_id', $mapelId)->exists();
        abort_unless($isPengampu, 403, 'Anda tidak mengampu mapel ini di rombel tersebut.');
    }
}
