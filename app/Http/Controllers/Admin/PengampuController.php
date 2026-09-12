<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePengampuRequest;
use App\Http\Requests\Admin\UpdatePengampuRequest;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PengampuController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pengampus.view', only: ['index']),
            new Middleware('permission:pengampus.create', only: ['create', 'store']),
            new Middleware('permission:pengampus.edit', only: ['edit', 'update']),
            new Middleware('permission:pengampus.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $tahunMode = request()->query('tahun', 'aktif');

        $pengampus = Pengampu::with(['guru', 'mataPelajaran', 'rombel.kelas', 'rombel.tahunAjaran'])
            ->when($tahunMode === 'aktif' && $tahunAktif, fn ($q) => $q->whereHas('rombel', fn ($qq) => $qq->where('tahun_ajaran_id', $tahunAktif->id)))
            ->when(is_numeric($tahunMode), fn ($q) => $q->whereHas('rombel', fn ($qq) => $qq->where('tahun_ajaran_id', $tahunMode)))
            ->when(request('search'), fn ($q, $s) => $q->whereHas('guru', fn ($qq) => $qq->where('nama', 'like', "%{$s}%")))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.pengampus.index', [
            'pengampus' => $pengampus,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => $tahunAktif,
            'tahunMode' => $tahunMode,
        ]);
    }

    public function create(): View
    {
        return view('admin.pengampus.create', $this->formData());
    }

    public function store(StorePengampuRequest $request): RedirectResponse
    {
        $pengampu = Pengampu::create($request->validated());

        return redirect()->route('admin.pengampus.index')->with('success', "Penugasan {$pengampu->guru->nama} ({$pengampu->mataPelajaran->kode} {$pengampu->rombel->kelas->nama_kelas}) berhasil ditambahkan.");
    }

    public function edit(Pengampu $pengampu): View
    {
        return view('admin.pengampus.edit', array_merge(['pengampu' => $pengampu->load('rombel')], $this->formData()));
    }

    public function update(UpdatePengampuRequest $request, Pengampu $pengampu): RedirectResponse
    {
        $pengampu->update($request->validated());

        return redirect()->route('admin.pengampus.index')->with('success', 'Penugasan diperbarui. Riwayat rombel lain tidak berubah.');
    }

    public function destroy(Pengampu $pengampu): RedirectResponse
    {
        $pengampu->delete();

        return back()->with('success', 'Penugasan dihapus.');
    }

    /**
     * Opsi dropdown form (guru/mapel aktif + rombel tahun aktif dulu).
     */
    protected function formData(): array
    {
        $tahunAktif = TahunAjaran::aktif()->first();

        return [
            'gurus' => Guru::aktif()->orderBy('nama')->get(),
            'mapels' => MataPelajaran::aktif()->orderBy('kode')->get(),
            'rombels' => Rombel::with(['kelas', 'tahunAjaran'])
                ->when($tahunAktif, fn ($q) => $q->orderByRaw('tahun_ajaran_id = ? desc', [$tahunAktif->id]))
                ->orderByDesc('tahun_ajaran_id')
                ->orderBy('kelas_id')
                ->get(),
        ];
    }
}
