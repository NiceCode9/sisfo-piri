<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGaleriRequest;
use App\Http\Requests\Admin\UpdateGaleriRequest;
use App\Models\Galeri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GaleriController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:galeris.view', only: ['index']),
            new Middleware('permission:galeris.create', only: ['create', 'store']),
            new Middleware('permission:galeris.edit', only: ['edit', 'update']),
            new Middleware('permission:galeris.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $galeris = Galeri::when(request('search'), fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when(request('tipe'), fn ($q, $t) => $q->where('tipe', $t))
            ->orderBy('order')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.galeris.index', compact('galeris'));
    }

    public function create(): View
    {
        return view('admin.galeris.create');
    }

    public function store(StoreGaleriRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('galeri', 'public');
        }
        unset($validated['image']);

        $galeri = Galeri::create($validated);

        return redirect()->route('admin.galeris.index')->with('success', "Galeri {$galeri->title} berhasil ditambahkan.");
    }

    public function edit(Galeri $galeri): View
    {
        return view('admin.galeris.edit', compact('galeri'));
    }

    public function update(UpdateGaleriRequest $request, Galeri $galeri): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            if ($galeri->image_path) {
                Storage::disk('public')->delete($galeri->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('galeri', 'public');
        }
        unset($validated['image']);

        $galeri->update($validated);

        return redirect()->route('admin.galeris.index')->with('success', "Galeri {$galeri->title} diperbarui.");
    }

    public function destroy(Galeri $galeri): RedirectResponse
    {
        if ($galeri->image_path) {
            Storage::disk('public')->delete($galeri->image_path);
        }
        $galeri->delete();

        return redirect()->route('admin.galeris.index')->with('success', "Galeri {$galeri->title} dihapus.");
    }
}
