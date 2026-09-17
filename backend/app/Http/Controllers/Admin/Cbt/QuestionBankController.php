<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\QuestionBank;
use App\Policies\QuestionBankPolicy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class QuestionBankController extends Controller implements HasMiddleware
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
        $user = $request->user();
        $query = QuestionBank::with(['mataPelajaran', 'guru'])->withCount('questions')->latest();

        if (! $user->hasRole(['super-admin', 'admin'])) {
            $guru = Guru::where('user_id', $user->id)->first();
            $query->where('guru_id', $guru?->id ?? -1);
        }

        if ($request->filled('mapel')) {
            $query->where('mata_pelajaran_id', $request->integer('mapel'));
        }

        $banks = $query->paginate(10)->withQueryString();
        $mapels = MataPelajaran::aktif()->orderBy('nama')->get();

        return view('admin.cbt.banks.index', compact('banks', 'mapels'));
    }

    public function create(Request $request): View
    {
        $mapels = $this->mapelsForCurrentUser($request->user());

        return view('admin.cbt.banks.create', compact('mapels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $mapelsForUser = $this->mapelsForCurrentUser($request->user())->pluck('id')->all();

        $data = $request->validate([
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajarans,id'],
            'nama' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'is_shared' => ['sometimes', 'boolean'],
        ]);

        if (! in_array((int) $data['mata_pelajaran_id'], $mapelsForUser, true) && ! $request->user()->hasRole(['super-admin', 'admin'])) {
            abort(403, 'Anda tidak mengampu mata pelajaran ini.');
        }

        $guru = Guru::where('user_id', $request->user()->id)->first();
        $guruId = $guru?->id;

        // Admin/super-admin tanpa baris guru tetap bisa buat bank — pakai guru pertama sebagai owner fallback
        if ($guruId === null) {
            $guruId = Guru::aktif()->value('id') ?? Guru::value('id');
            abort_unless($guruId !== null, 422, 'Belum ada data guru.');
        }

        // Cek policy pengampu untuk mapel
        if (! QuestionBankPolicy::isPengampuForMapel($request->user(), (int) $data['mata_pelajaran_id'])) {
            abort(403, 'Anda tidak mengampu mata pelajaran ini.');
        }

        if (QuestionBank::where('guru_id', $guruId)->where('mata_pelajaran_id', $data['mata_pelajaran_id'])->where('nama', $data['nama'])->exists()) {
            return back()->withErrors(['nama' => 'Nama bank sudah dipakai untuk mapel ini.'])->withInput();
        }

        QuestionBank::create([
            'mata_pelajaran_id' => $data['mata_pelajaran_id'],
            'guru_id' => $guruId,
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'is_shared' => $request->boolean('is_shared'),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.cbt.banks.index')->with('success', 'Bank soal dibuat.');
    }

    public function show(QuestionBank $bank): View
    {
        $this->authorize('view', $bank);
        $bank->load(['mataPelajaran', 'guru', 'questions' => fn ($q) => $q->orderBy('order')->orderBy('id')]);

        return view('admin.cbt.banks.show', compact('bank'));
    }

    public function edit(QuestionBank $bank): View
    {
        $this->authorize('update', $bank);
        $mapels = $this->mapelsForCurrentUser(request()->user());

        return view('admin.cbt.banks.edit', compact('bank', 'mapels'));
    }

    public function update(Request $request, QuestionBank $bank): RedirectResponse
    {
        $this->authorize('update', $bank);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'is_shared' => ['sometimes', 'boolean'],
        ]);

        if (QuestionBank::where('guru_id', $bank->guru_id)->where('mata_pelajaran_id', $bank->mata_pelajaran_id)->where('nama', $data['nama'])->where('id', '!=', $bank->id)->exists()) {
            return back()->withErrors(['nama' => 'Nama bank sudah dipakai untuk mapel ini.'])->withInput();
        }

        $bank->update([
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'is_shared' => $request->boolean('is_shared'),
        ]);

        return redirect()->route('admin.cbt.banks.show', $bank)->with('success', 'Bank soal diperbarui.');
    }

    public function destroy(QuestionBank $bank): RedirectResponse
    {
        $this->authorize('delete', $bank);
        $bank->delete();

        return redirect()->route('admin.cbt.banks.index')->with('success', 'Bank soal dihapus. Soal snapshot di ujian tetap aman.');
    }

    /**
     * @return Collection<int, MataPelajaran>
     */
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
}
