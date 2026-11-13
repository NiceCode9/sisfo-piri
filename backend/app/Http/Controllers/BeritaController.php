<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\KategoriBerita;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BeritaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Berita::published()->with(['kategori', 'tags']);

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($qBuilder) use ($q) {
                $qBuilder->where('judul', 'like', "%{$q}%")
                    ->orWhere('ringkasan', 'like', "%{$q}%");
            });
        }

        if ($kategoriSlug = $request->query('kategori')) {
            $query->whereHas('kategori', fn ($qBuilder) => $qBuilder->where('slug', $kategoriSlug));
        }

        if ($tagSlug = $request->query('tag')) {
            $query->whereHas('tags', fn ($qBuilder) => $qBuilder->where('slug', $tagSlug));
        }

        $beritas = $query->orderByDesc('published_at')->paginate(9)->withQueryString();

        $kategoris = KategoriBerita::orderBy('nama')->get();
        $tags = Tag::orderBy('nama')->get();

        return view('spmb.berita.index', compact('beritas', 'kategoris', 'tags'));
    }

    public function show(Berita $berita): View
    {
        abort_unless($berita->is_published, 404);

        $berita->increment('views_count');

        $related = Berita::published()
            ->where('kategori_berita_id', $berita->kategori_berita_id)
            ->where('id', '!=', $berita->id)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('spmb.berita.show', compact('berita', 'related'));
    }
}
