<?php

namespace App\Http\Controllers\Jurnalistik;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(): View
    {
        $tags = Tag::withCount('beritas')->latest()->paginate(15);

        return view('jurnalistik.tags.index', compact('tags'));
    }

    public function create(): View
    {
        return view('jurnalistik.tags.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        $tag = Tag::create([
            'nama' => $validated['nama'],
            'slug' => $this->resolveSlug($validated['slug'] ?? null, $validated['nama']),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Tag dibuat.', 'data' => $tag]);
        }

        return redirect()->route('jurnalistik.tags.index')->with('success', 'Tag dibuat.');
    }

    public function edit(Tag $tag): View
    {
        return view('jurnalistik.tags.edit', compact('tag'));
    }

    public function update(Request $request, Tag $tag): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        $tag->update([
            'nama' => $validated['nama'],
            'slug' => $this->resolveSlug($validated['slug'] ?? null, $validated['nama'], $tag->id),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Tag diperbarui.', 'data' => $tag->fresh()]);
        }

        return redirect()->route('jurnalistik.tags.index')->with('success', 'Tag diperbarui.');
    }

    public function destroy(Tag $tag): RedirectResponse|JsonResponse
    {
        $tag->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Tag dihapus.']);
        }

        return redirect()->route('jurnalistik.tags.index')->with('success', 'Tag dihapus.');
    }

    private function resolveSlug(?string $slug, string $nama, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $nama);
        if ($base === '') {
            $base = 'tag';
        }
        $candidate = $base;
        $i = 2;
        while (Tag::where('slug', $candidate)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }
}
