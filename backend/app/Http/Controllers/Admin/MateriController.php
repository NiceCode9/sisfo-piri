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
        ];
    }

    public function index(): View
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $tahunMode = request()->query('tahun', 'aktif');

        $materis = Materi::with(['rombel.kelas', 'rombel.tahunAjaran', 'mataPelajaran', 'guru'])
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
            'rombels' => Rombel::with(['kelas', 'tahunAjaran'])
                ->when($tahunAktif, fn ($q) => $q->orderByRaw('tahun_ajaran_id = ? desc', [$tahunAktif->id]))
                ->orderByDesc('tahun_ajaran_id')->orderBy('kelas_id')->get(),
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
        $validated['guru_id'] = Guru::where('user_id', auth()->id())->first()?->id;
        $validated['is_aktif'] = $request->boolean('is_aktif', true);

        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store('materi', 'public');
        }

        unset($validated['file']);
        $materi = Materi::create($validated);

        return redirect()->route('admin.materis.index')->with('success', "Materi {$materi->judul} ditambahkan.");
    }

    public function show(Materi $materi): View
    {
        $materi->load(['rombel.kelas', 'rombel.tahunAjaran', 'mataPelajaran', 'guru']);

        return view('admin.materis.show', compact('materi'));
    }

    public function edit(Materi $materi): View
    {
        return view('admin.materis.edit', array_merge(['materi' => $materi], $this->formData()));
    }

    public function update(UpdateMateriRequest $request, Materi $materi): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_aktif'] = $request->boolean('is_aktif');

        if ($request->hasFile('file')) {
            if ($materi->file_path) {
                Storage::disk('public')->delete($materi->file_path);
            }
            $validated['file_path'] = $request->file('file')->store('materi', 'public');
        }

        unset($validated['file']);
        $materi->update($validated);

        return redirect()->route('admin.materis.index')->with('success', 'Materi diperbarui.');
    }

    public function destroy(Materi $materi): RedirectResponse
    {
        if ($materi->file_path) {
            Storage::disk('public')->delete($materi->file_path);
        }

        $materi->delete();

        return redirect()->route('admin.materis.index')->with('success', 'Materi dihapus.');
    }

    protected function formData(): array
    {
        $tahunAktif = TahunAjaran::aktif()->first();

        return [
            'rombels' => Rombel::with(['kelas', 'tahunAjaran'])
                ->when($tahunAktif, fn ($q) => $q->orderByRaw('tahun_ajaran_id = ? desc', [$tahunAktif->id]))
                ->orderByDesc('tahun_ajaran_id')->orderBy('kelas_id')->get(),
            'mapels' => MataPelajaran::aktif()->orderBy('kode')->get(),
        ];
    }
}
