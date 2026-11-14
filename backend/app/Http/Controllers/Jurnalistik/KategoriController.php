<?php

namespace App\Http\Controllers\Jurnalistik;

use App\Http\Controllers\Controller;
use App\Models\KategoriBerita;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class KategoriController extends Controller
{
    public function index(): View
    {
        $kategoris = KategoriBerita::withCount('beritas')->latest()->paginate(15);

        return view('jurnalistik.kategoris.index', compact('kategoris'));
    }

    public function create(): View
    {
        return view('jurnalistik.kategoris.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $kategori = KategoriBerita::create([
            'nama' => $validated['nama'],
            'slug' => $this->resolveSlug($validated['slug'] ?? null, $validated['nama']),
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Kategori dibuat.', 'data' => $kategori]);
        }

        return redirect()->route('jurnalistik.kategoris.index')->with('success', 'Kategori dibuat.');
    }

    public function edit(KategoriBerita $kategori): View
    {
        return view('jurnalistik.kategoris.edit', ['kategori' => $kategori]);
    }

    public function update(Request $request, KategoriBerita $kategori): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $kategori->update([
            'nama' => $validated['nama'],
            'slug' => $this->resolveSlug($validated['slug'] ?? null, $validated['nama'], $kategori->id),
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Kategori diperbarui.', 'data' => $kategori->fresh()]);
        }

        return redirect()->route('jurnalistik.kategoris.index')->with('success', 'Kategori diperbarui.');
    }

    public function destroy(KategoriBerita $kategori): RedirectResponse|JsonResponse
    {
        $kategori->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Kategori dihapus.']);
        }

        return redirect()->route('jurnalistik.kategoris.index')->with('success', 'Kategori dihapus.');
    }

    private function resolveSlug(?string $slug, string $nama, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $nama);
        if ($base === '') {
            $base = 'kategori';
        }
        $candidate = $base;
        $i = 2;
        while (KategoriBerita::where('slug', $candidate)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }
}
