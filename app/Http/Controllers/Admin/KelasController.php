<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreKelasRequest;
use App\Http\Requests\Admin\UpdateKelasRequest;
use App\Models\Kelas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class KelasController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:kelas.view', only: ['index']),
            new Middleware('permission:kelas.create', only: ['create', 'store']),
            new Middleware('permission:kelas.edit', only: ['edit', 'update']),
            new Middleware('permission:kelas.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $kelas = Kelas::withCount(['siswas', 'pengampus', 'riwayatKelas'])
            ->when(request('search'), fn ($q, $s) => $q->where('nama_kelas', 'like', "%{$s}%"))
            ->when(request('tingkat'), fn ($q, $t) => $q->where('tingkat', $t))
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->paginate(10)
            ->withQueryString();

        return view('admin.kelas.index', compact('kelas'));
    }

    public function create(): View
    {
        return view('admin.kelas.create');
    }

    public function store(StoreKelasRequest $request): RedirectResponse
    {
        $kelas = Kelas::create($request->validated());

        return redirect()->route('admin.kelas.index')->with('success', "Kelas {$kelas->nama_kelas} berhasil ditambahkan.");
    }

    public function edit(Kelas $kelas): View
    {
        return view('admin.kelas.edit', compact('kelas'));
    }

    public function update(UpdateKelasRequest $request, Kelas $kelas): RedirectResponse
    {
        $kelas->update($request->validated());

        return redirect()->route('admin.kelas.index')->with('success', "Kelas {$kelas->nama_kelas} diperbarui.");
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        if ($kelas->siswas()->exists() || $kelas->pengampus()->exists() || $kelas->riwayatKelas()->exists()) {
            return back()->with('error', "Kelas {$kelas->nama_kelas} masih memiliki data terkait dan tidak dapat dihapus.");
        }

        $kelas->delete();

        return redirect()->route('admin.kelas.index')->with('success', "Kelas {$kelas->nama_kelas} dihapus.");
    }
}
