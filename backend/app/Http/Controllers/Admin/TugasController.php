<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTugasRequest;
use App\Http\Requests\Admin\UpdateTugasRequest;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class TugasController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:tugas.view', only: ['index', 'show']),
            new Middleware('permission:tugas.create', only: ['create', 'store']),
            new Middleware('permission:tugas.edit', only: ['edit', 'update']),
            new Middleware('permission:tugas.delete', only: ['destroy']),
            new Middleware('permission:tugas.nilai', only: ['nilai', 'simpanNilai']),
        ];
    }

    public function index(): View
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $tahunMode = request()->query('tahun', 'aktif');

        $tugas = Tugas::with(['rombel.kelas', 'rombel.tahunAjaran', 'mataPelajaran', 'guru'])
            ->when($tahunMode === 'aktif' && $tahunAktif, fn ($q) => $q->whereHas('rombel', fn ($qq) => $qq->where('tahun_ajaran_id', $tahunAktif->id)))
            ->when(is_numeric($tahunMode), fn ($q) => $q->whereHas('rombel', fn ($qq) => $qq->where('tahun_ajaran_id', $tahunMode)))
            ->when(request('rombel'), fn ($q, $v) => $q->where('rombel_id', $v))
            ->when(request('search'), fn ($q, $s) => $q->where('judul', 'like', "%{$s}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.tugas.index', [
            'tugas' => $tugas,
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
        return view('admin.tugas.create', $this->formData());
    }

    public function store(StoreTugasRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['guru_id'] = Guru::where('user_id', auth()->id())->first()?->id;
        $validated['is_aktif'] = $request->boolean('is_aktif', true);
        $tugas = Tugas::create($validated);

        return redirect()->route('admin.tugas.index')->with('success', "Tugas {$tugas->judul} ditambahkan.");
    }

    public function show(Tugas $tuga): View
    {
        $tuga->load(['rombel.kelas', 'rombel.tahunAjaran', 'mataPelajaran', 'guru', 'pengumpulans.siswa.user']);

        return view('admin.tugas.show', ['tugas' => $tuga]);
    }

    public function edit(Tugas $tuga): View
    {
        return view('admin.tugas.edit', array_merge(['tugas' => $tuga], $this->formData()));
    }

    public function update(UpdateTugasRequest $request, Tugas $tuga): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_aktif'] = $request->boolean('is_aktif');
        $tuga->update($validated);

        return redirect()->route('admin.tugas.index')->with('success', 'Tugas diperbarui.');
    }

    public function destroy(Tugas $tuga): RedirectResponse
    {
        $tuga->delete();

        return redirect()->route('admin.tugas.index')->with('success', 'Tugas dihapus.');
    }

    public function nilai(Tugas $tuga): View
    {
        $tuga->load(['rombel', 'mataPelajaran']);
        $pengumpulans = $tuga->pengumpulans()->with('siswa.user')->latest()->get();

        return view('admin.tugas.nilai', ['tugas' => $tuga, 'pengumpulans' => $pengumpulans]);
    }

    public function simpanNilai(Request $request, Tugas $tuga): RedirectResponse
    {
        $request->validate([
            'nilai' => ['required', 'array'],
            'nilai.*' => ['nullable', 'integer', 'min:0', 'max:100'],
            'catatan_guru' => ['nullable', 'array'],
            'catatan_guru.*' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($request->input('nilai', []) as $id => $nilai) {
            $pengumpulan = PengumpulanTugas::where('id', $id)->where('tugas_id', $tuga->id)->first();

            if (! $pengumpulan) {
                continue;
            }

            $pengumpulan->update([
                'nilai' => $nilai === '' ? null : $nilai,
                'catatan_guru' => $request->input("catatan_guru.{$id}"),
            ]);
        }

        return redirect()->route('admin.tugas.show', $tuga)->with('success', 'Nilai disimpan.');
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
