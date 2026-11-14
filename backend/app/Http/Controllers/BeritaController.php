<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\BeritaView;
use App\Models\KategoriBerita;
use App\Models\Komentar;
use App\Models\Like;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        $populer = Berita::published()->orderByDesc('views_count')->limit(5)->get();

        return view('spmb.berita.index', compact('beritas', 'kategoris', 'tags', 'populer'));
    }

    public function show(Berita $berita): View
    {
        abort_unless($berita->is_published, 404);

        $berita->increment('views_count');
        $berita->load(['penulis', 'tags', 'kategori']);

        // Catat view untuk analitik (durasi baca di-update via heartbeat).
        BeritaView::create([
            'berita_id' => $berita->id,
            'user_id' => auth()->id(),
            'ip_hash' => hash('sha256', (string) request()->ip()),
            'session_id' => (string) request()->session()->getId(),
            'duration_seconds' => 0,
        ]);

        $related = Berita::published()
            ->where('kategori_berita_id', $berita->kategori_berita_id)
            ->where('id', '!=', $berita->id)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        $komentars = $berita->komentars()->approved()->latest()->get();
        $liked = auth()->check() && $berita->likes()->where('user_id', auth()->id())->exists();

        return view('spmb.berita.show', compact('berita', 'related', 'komentars', 'liked'));
    }

    public function recordRead(Request $request, Berita $berita): JsonResponse
    {
        $duration = (int) $request->input('duration', 0);
        if ($duration < 0) {
            $duration = 0;
        }
        if ($duration > 3600) {
            $duration = 3600;
        }

        // Update view record terbaru untuk sesi/ip ini (dalam 1 jam terakhir).
        $view = BeritaView::where('berita_id', $berita->id)
            ->where('session_id', (string) $request->session()->getId())
            ->where('created_at', '>=', now()->subHour())
            ->latest('id')
            ->first();

        if ($view) {
            if ($duration > $view->duration_seconds) {
                $view->update(['duration_seconds' => $duration]);
            }
        } else {
            BeritaView::create([
                'berita_id' => $berita->id,
                'user_id' => auth()->id(),
                'ip_hash' => hash('sha256', (string) $request->ip()),
                'session_id' => (string) $request->session()->getId(),
                'duration_seconds' => $duration,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function storeKomentar(Request $request, Berita $berita): RedirectResponse
    {
        abort_unless($berita->is_published, 404);

        if ($request->filled('website')) {
            return back();
        }

        $guest = $request->user() === null;
        $validated = $request->validate([
            'nama' => [$guest ? 'required' : 'nullable', 'string', 'max:100'],
            'email' => [$guest ? 'required' : 'nullable', 'email', 'max:150'],
            'isi' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        Komentar::create([
            'berita_id' => $berita->id,
            'user_id' => $user?->id,
            'nama' => $user ? $user->name : $validated['nama'],
            'email' => $user ? $user->email : $validated['email'],
            'isi' => strip_tags($validated['isi']),
            'status' => 'pending',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Komentar menunggu persetujuan.');
    }

    public function toggleLike(Request $request, Berita $berita): RedirectResponse
    {
        abort_unless($berita->is_published, 404);

        $existing = Like::where('berita_id', $berita->id)->where('user_id', $request->user()->id)->first();
        if ($existing) {
            $existing->delete();
        } else {
            Like::create([
                'berita_id' => $berita->id,
                'user_id' => $request->user()->id,
            ]);
        }

        return back();
    }
}
