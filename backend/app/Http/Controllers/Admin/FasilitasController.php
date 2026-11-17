<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFasilitasRequest;
use App\Http\Requests\Admin\UpdateFasilitasRequest;
use App\Models\Fasilitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FasilitasController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:fasilitas.view', only: ['index']),
            new Middleware('permission:fasilitas.create', only: ['create', 'store']),
            new Middleware('permission:fasilitas.edit', only: ['edit', 'update']),
            new Middleware('permission:fasilitas.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $fasilitas = Fasilitas::query()
            ->when(request('search'), fn ($q, $s) => $q->where(
                fn ($qq) => $qq->where('nama', 'like', "%{$s}%")
                    ->orWhere('jenis', 'like', "%{$s}%")
                    ->orWhere('deskripsi', 'like', "%{$s}%")
            ))
            ->when(request('jenis'), fn ($q, $j) => $q->where('jenis', $j))
            ->orderBy('jenis')
            ->orderBy('urutan')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $jenisList = Fasilitas::query()->distinct()->orderBy('jenis')->pluck('jenis');

        return view('admin.fasilitas.index', compact('fasilitas', 'jenisList'));
    }

    public function create(): View
    {
        $jenisList = Fasilitas::query()->distinct()->orderBy('jenis')->pluck('jenis');

        return view('admin.fasilitas.create', compact('jenisList'));
    }

    public function store(StoreFasilitasRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $files = $validated['fotos'] ?? [];
        unset($validated['fotos']);

        $validated['aktif'] = (bool) ($validated['aktif'] ?? false);
        $validated['urutan'] = (int) ($validated['urutan'] ?? 0);

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->store('fasilitas', 'public');
        }
        $validated['fotos'] = $paths ?: null;

        $fasilitas = Fasilitas::create($validated);

        return redirect()->route('admin.fasilitas.index')->with('success', "Fasilitas {$fasilitas->nama} berhasil ditambahkan.");
    }

    public function edit(Fasilitas $fasilitas): View
    {
        $jenisList = Fasilitas::query()->distinct()->orderBy('jenis')->pluck('jenis');

        return view('admin.fasilitas.edit', compact('fasilitas', 'jenisList'));
    }

    public function update(UpdateFasilitasRequest $request, Fasilitas $fasilitas): RedirectResponse
    {
        $validated = $request->validated();
        $files = $validated['fotos'] ?? [];
        $hapus = $validated['hapus_foto'] ?? [];
        unset($validated['fotos'], $validated['hapus_foto']);

        $validated['aktif'] = (bool) ($validated['aktif'] ?? false);
        $validated['urutan'] = (int) ($validated['urutan'] ?? 0);

        $fotos = $fasilitas->fotos ?? [];
        foreach ($hapus as $path) {
            if (in_array($path, $fotos, true)) {
                Storage::disk('public')->delete($path);
                $fotos = array_values(array_diff($fotos, [$path]));
            }
        }
        foreach ($files as $file) {
            $fotos[] = $file->store('fasilitas', 'public');
        }
        $validated['fotos'] = $fotos ?: null;

        $fasilitas->update($validated);

        return redirect()->route('admin.fasilitas.index')->with('success', "Fasilitas {$fasilitas->nama} diperbarui.");
    }

    public function destroy(Fasilitas $fasilitas): RedirectResponse
    {
        foreach ($fasilitas->fotos ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }
        $fasilitas->delete();

        return redirect()->route('admin.fasilitas.index')->with('success', "Fasilitas {$fasilitas->nama} dihapus.");
    }
}
