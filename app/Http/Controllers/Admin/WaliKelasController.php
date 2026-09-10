<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWaliKelasRequest;
use App\Http\Requests\Admin\UpdateWaliKelasRequest;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\WaliKelas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class WaliKelasController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wali-kelas.view', only: ['index']),
            new Middleware('permission:wali-kelas.create', only: ['create', 'store']),
            new Middleware('permission:wali-kelas.edit', only: ['edit', 'update']),
            new Middleware('permission:wali-kelas.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $walis = WaliKelas::with(['guru', 'kelas', 'tahunAjaran'])
            ->when(request('tahun'), fn ($q, $t) => $q->where('tahun_ajaran_id', $t))
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.wali-kelas.index', [
            'walis' => $walis,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.wali-kelas.create', $this->formData());
    }

    public function store(StoreWaliKelasRequest $request): RedirectResponse
    {
        $wali = WaliKelas::create($request->validated());

        return redirect()->route('admin.wali-kelas.index')->with('success', "Wali {$wali->kelas->nama_kelas}: {$wali->guru->nama} berhasil ditetapkan.");
    }

    public function edit(WaliKelas $waliKelas): View
    {
        return view('admin.wali-kelas.edit', array_merge(['wali' => $waliKelas], $this->formData()));
    }

    public function update(UpdateWaliKelasRequest $request, WaliKelas $waliKelas): RedirectResponse
    {
        $waliKelas->update($request->validated());

        return redirect()->route('admin.wali-kelas.index')->with('success', 'Wali kelas diperbarui.');
    }

    public function destroy(WaliKelas $waliKelas): RedirectResponse
    {
        $waliKelas->delete();

        return redirect()->route('admin.wali-kelas.index')->with('success', 'Wali kelas dihapus.');
    }

    /**
     * Opsi dropdown form.
     */
    protected function formData(): array
    {
        return [
            'gurus' => Guru::aktif()->orderBy('nama')->get(),
            'kelasList' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => TahunAjaran::aktif()->first(),
        ];
    }
}
