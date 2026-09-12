<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGelombangRequest;
use App\Http\Requests\Admin\UpdateGelombangRequest;
use App\Models\Gelombang;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class GelombangController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:gelombangs.view', only: ['index']),
            new Middleware('permission:gelombangs.create', only: ['create', 'store']),
            new Middleware('permission:gelombangs.edit', only: ['edit', 'update']),
            new Middleware('permission:gelombangs.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $tahunMode = request()->query('tahun', 'aktif');

        $gelombangs = Gelombang::with('tahunAjaran')
            ->when(request('search'), fn ($q, $s) => $q->where('nama_gelombang', 'like', "%{$s}%"))
            ->when($tahunMode === 'aktif' && $tahunAktif, fn ($q) => $q->where('tahun_ajaran_id', $tahunAktif->id))
            ->when(is_numeric($tahunMode), fn ($q) => $q->where('tahun_ajaran_id', $tahunMode))
            ->orderBy('nomor_urut')
            ->paginate(10)
            ->withQueryString();

        return view('admin.gelombangs.index', [
            'gelombangs' => $gelombangs,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => $tahunAktif,
            'tahunMode' => $tahunMode,
        ]);
    }

    public function create(): View
    {
        return view('admin.gelombangs.create', [
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => TahunAjaran::aktif()->first(),
        ]);
    }

    public function store(StoreGelombangRequest $request): RedirectResponse
    {
        $gelombang = Gelombang::create($request->validated());

        return redirect()->route('admin.gelombangs.index')->with('success', "Gelombang {$gelombang->nama_gelombang} berhasil ditambahkan.");
    }

    public function edit(Gelombang $gelombang): View
    {
        return view('admin.gelombangs.edit', [
            'gelombang' => $gelombang,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function update(UpdateGelombangRequest $request, Gelombang $gelombang): RedirectResponse
    {
        $gelombang->update($request->validated());

        return redirect()->route('admin.gelombangs.index')->with('success', "Gelombang {$gelombang->nama_gelombang} diperbarui.");
    }

    public function destroy(Gelombang $gelombang): RedirectResponse
    {
        $gelombang->delete();

        return redirect()->route('admin.gelombangs.index')->with('success', "Gelombang {$gelombang->nama_gelombang} dihapus.");
    }
}
