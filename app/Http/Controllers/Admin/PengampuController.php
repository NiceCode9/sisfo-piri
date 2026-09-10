<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePengampuRequest;
use App\Http\Requests\Admin\UpdatePengampuRequest;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
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
        $pengampus = Pengampu::with(['guru', 'mataPelajaran', 'kelas', 'tahunAjaran'])
            ->when(request('tahun'), fn ($q, $t) => $q->where('tahun_ajaran_id', $t))
            ->when(request('kelas'), fn ($q, $k) => $q->where('kelas_id', $k))
            ->when(request('search'), fn ($q, $s) => $q->whereHas('guru', fn ($qq) => $qq->where('nama', 'like', "%{$s}%")))
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.pengampus.index', [
            'pengampus' => $pengampus,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'kelasList' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pengampus.create', $this->formData());
    }

    public function store(StorePengampuRequest $request): RedirectResponse
    {
        $pengampu = Pengampu::create($request->validated());

        return redirect()->route('admin.pengampus.index')->with('success', "Penugasan {$pengampu->guru->nama} ({$pengampu->mataPelajaran->kode} {$pengampu->kelas->nama_kelas}) berhasil ditambahkan.");
    }

    public function edit(Pengampu $pengampu): View
    {
        return view('admin.pengampus.edit', array_merge(['pengampu' => $pengampu], $this->formData()));
    }

    public function update(UpdatePengampuRequest $request, Pengampu $pengampu): RedirectResponse
    {
        $pengampu->update($request->validated());

        return redirect()->route('admin.pengampus.index')->with('success', 'Penugasan diperbarui. Riwayat tahun lain tidak berubah.');
    }

    public function destroy(Pengampu $pengampu): RedirectResponse
    {
        $pengampu->delete();

        return redirect()->route('admin.pengampus.index')->with('success', 'Penugasan dihapus.');
    }

    /**
     * Opsi dropdown form (guru/mapel/kelas aktif + semua tahun).
     */
    protected function formData(): array
    {
        return [
            'gurus' => Guru::aktif()->orderBy('nama')->get(),
            'mapels' => MataPelajaran::aktif()->orderBy('kode')->get(),
            'kelasList' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => TahunAjaran::aktif()->first(),
        ];
    }
}
