<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMateriRequest;
use App\Http\Requests\Admin\UpdateMateriRequest;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Materi;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MateriController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:materis.view', only: ['index', 'show']),
            new Middleware('permission:materis.create', only: ['create', 'store']),
            new Middleware('permission:materis.edit', only: ['edit', 'update']),
            new Middleware('permission:materis.delete', only: ['destroy']),
            new Middleware('throttle:elearning-kumpul', only: ['store']),
        ];
    }

    public function index(): View
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $tahunMode = request()->query('tahun', 'aktif');

        $materis = Materi::with(['rombel.kelas', 'rombel.tahunAjaran', 'mataPelajaran', 'guru'])
            ->whereHas('rombel', fn ($qq) => $qq->terjangkauUser(request()->user()))
            ->when($tahunMode === 'aktif' && $tahunAktif, fn ($q) => $q->whereHas('rombel', fn ($qq) => $qq->where('tahun_ajaran_id', $tahunAktif->id)))
            ->when(is_numeric($tahunMode), fn ($q) => $q->whereHas('rombel', fn ($qq) => $qq->where('tahun_ajaran_id', $tahunMode)))
            ->when(request('rombel'), fn ($q, $v) => $q->where('rombel_id', $v))
            ->when(request('tipe'), fn ($q, $v) => $q->where('tipe', $v))
            ->when(request('search'), fn ($q, $s) => $q->where('judul', 'like', "%{$s}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.materis.index', [
            'materis' => $materis,
            'rombels' => $this->rombels(),
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => $tahunAktif,
            'tahunMode' => $tahunMode,
        ]);
    }

    public function create(): View
    {
        return view('admin.materis.create', $this->formData());
    }

    public function store(StoreMateriRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Admin tidak punya baris `gurus`, jadi guru_id sengaja dibiarkan null
        // daripada diisi wali rombel: atribusi harus menunjuk orang yang
        // benar-benar membuat materi, bukan pengganti yang menyesatkan.
        $validated['guru_id'] = Guru::where('user_id', $request->user()->id)->first()?->id;
        $validated['is_aktif'] = $request->boolean('is_aktif', true);

        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store('materi', Materi::DISK);
        }

        unset($validated['file']);
        $materi = Materi::create($validated);

        return redirect()->route('admin.materis.index')->with('success', "Materi {$materi->judul} ditambahkan.");
    }

    public function show(Materi $materi): View
    {
        $this->authorize('view', $materi);

        $materi->load(['rombel.kelas', 'rombel.tahunAjaran', 'mataPelajaran', 'guru']);

        return view('admin.materis.show', compact('materi'));
    }

    public function edit(Materi $materi): View
    {
        $this->authorize('update', $materi);

        return view('admin.materis.edit', array_merge(['materi' => $materi], $this->formData()));
    }

    public function update(UpdateMateriRequest $request, Materi $materi): RedirectResponse
    {
        $this->authorize('update', $materi);

        $validated = $request->validated();
        $validated['is_aktif'] = $request->boolean('is_aktif');

        if ($request->hasFile('file')) {
            if ($materi->file_path) {
                Storage::disk(Materi::DISK)->delete($materi->file_path);
            }
            $validated['file_path'] = $request->file('file')->store('materi', Materi::DISK);
        }

        unset($validated['file']);
        $materi->update($validated);

        return redirect()->route('admin.materis.index')->with('success', 'Materi diperbarui.');
    }

    public function destroy(Materi $materi): RedirectResponse
    {
        $this->authorize('delete', $materi);

        if ($materi->file_path) {
            Storage::disk(Materi::DISK)->delete($materi->file_path);
        }

        $materi->delete();

        return redirect()->route('admin.materis.index')->with('success', 'Materi dihapus.');
    }

    /**
     * Rombel yang boleh dikelola user, terurut dari tahun ajaran aktif.
     *
     * @return Collection<int, Rombel>
     */
    protected function rombels(): Collection
    {
        $tahunAktif = TahunAjaran::aktif()->first();

        return Rombel::with(['kelas', 'tahunAjaran'])
            ->terjangkauUser(request()->user())
            ->when($tahunAktif, fn ($q) => $q->orderByRaw('tahun_ajaran_id = ? desc', [$tahunAktif->id]))
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->get();
    }

    protected function formData(): array
    {
        return [
            'rombels' => $this->rombels(),
            'mapels' => MataPelajaran::aktif()->orderBy('kode')->get(),
        ];
    }
}
