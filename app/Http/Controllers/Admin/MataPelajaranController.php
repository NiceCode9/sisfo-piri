<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMataPelajaranRequest;
use App\Http\Requests\Admin\UpdateMataPelajaranRequest;
use App\Models\MataPelajaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class MataPelajaranController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:mata-pelajarans.view', only: ['index']),
            new Middleware('permission:mata-pelajarans.create', only: ['create', 'store']),
            new Middleware('permission:mata-pelajarans.edit', only: ['edit', 'update']),
            new Middleware('permission:mata-pelajarans.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $mapels = MataPelajaran::withCount('pengampus')
            ->when(request('search'), fn ($q, $s) => $q->where(fn ($qq) => $qq
                ->where('kode', 'like', "%{$s}%")
                ->orWhere('nama', 'like', "%{$s}%")
            ))
            ->orderBy('kode')
            ->paginate(10)
            ->withQueryString();

        return view('admin.mata-pelajarans.index', compact('mapels'));
    }

    public function create(): View
    {
        return view('admin.mata-pelajarans.create');
    }

    public function store(StoreMataPelajaranRequest $request): RedirectResponse
    {
        $mapel = MataPelajaran::create($request->validated());

        return redirect()->route('admin.mata-pelajarans.index')->with('success', "Mapel {$mapel->nama} berhasil ditambahkan.");
    }

    public function edit(MataPelajaran $mataPelajaran): View
    {
        return view('admin.mata-pelajarans.edit', ['mapel' => $mataPelajaran]);
    }

    public function update(UpdateMataPelajaranRequest $request, MataPelajaran $mataPelajaran): RedirectResponse
    {
        $mataPelajaran->update($request->validated());

        return redirect()->route('admin.mata-pelajarans.index')->with('success', "Mapel {$mataPelajaran->nama} diperbarui.");
    }

    public function destroy(MataPelajaran $mataPelajaran): RedirectResponse
    {
        if ($mataPelajaran->pengampus()->exists()) {
            return back()->with('error', "Mapel {$mataPelajaran->nama} masih dipakai penugasan dan tidak dapat dihapus. Nonaktifkan saja.");
        }

        $mataPelajaran->delete();

        return redirect()->route('admin.mata-pelajarans.index')->with('success', "Mapel {$mataPelajaran->nama} dihapus.");
    }
}
