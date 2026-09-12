<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePengumumanRequest;
use App\Http\Requests\Admin\UpdatePengumumanRequest;
use App\Models\Pengumuman;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PengumumanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pengumumans.view', only: ['index']),
            new Middleware('permission:pengumumans.create', only: ['create', 'store']),
            new Middleware('permission:pengumumans.edit', only: ['edit', 'update']),
            new Middleware('permission:pengumumans.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $tahunMode = request()->query('tahun', 'aktif');

        $pengumumans = Pengumuman::with('tahunAjaran')
            ->when(request('search'), fn ($q, $s) => $q->where('judul', 'like', "%{$s}%"))
            ->when(request('status') !== null && request('status') !== '', fn ($q) => $q->where('status_aktif', request('status') === '1'))
            ->when($tahunMode === 'aktif' && $tahunAktif, fn ($q) => $q->where('tahun_ajaran_id', $tahunAktif->id))
            ->when(is_numeric($tahunMode), fn ($q) => $q->where('tahun_ajaran_id', $tahunMode))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.pengumumans.index', [
            'pengumumans' => $pengumumans,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => $tahunAktif,
            'tahunMode' => $tahunMode,
        ]);
    }

    public function create(): View
    {
        return view('admin.pengumumans.create', [
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => TahunAjaran::aktif()->first(),
        ]);
    }

    public function store(StorePengumumanRequest $request): RedirectResponse
    {
        $pengumuman = Pengumuman::create($request->validated());

        return redirect()->route('admin.pengumumans.index')->with('success', "Pengumuman {$pengumuman->judul} berhasil ditambahkan.");
    }

    public function edit(Pengumuman $pengumuman): View
    {
        return view('admin.pengumumans.edit', [
            'pengumuman' => $pengumuman,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function update(UpdatePengumumanRequest $request, Pengumuman $pengumuman): RedirectResponse
    {
        $pengumuman->update($request->validated());

        return redirect()->route('admin.pengumumans.index')->with('success', "Pengumuman {$pengumuman->judul} diperbarui.");
    }

    public function destroy(Pengumuman $pengumuman): RedirectResponse
    {
        $pengumuman->delete();

        return redirect()->route('admin.pengumumans.index')->with('success', "Pengumuman {$pengumuman->judul} dihapus.");
    }
}
