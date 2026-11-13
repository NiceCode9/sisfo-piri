<?php

namespace App\Http\Controllers\Jurnalistik;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use App\Models\BeritaGambar;
use App\Models\KategoriBerita;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BeritaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [];
    }

    public function index(Request $request): View
    {
        $beritas = Berita::with(['kategori', 'penulis', 'tags'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('q'), fn ($q, $s) => $q->where('judul', 'like', "%{$s}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('jurnalistik.beritas.index', compact('beritas'));
    }

    public function create(): View
    {
        return view('jurnalistik.beritas.create', [
            'kategoris' => KategoriBerita::orderBy('nama')->get(),
            'tags' => Tag::orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string', 'max:500'],
            'konten' => ['nullable', 'string'],
            'kategori_berita_id' => ['required', 'exists:kategori_beritas,id'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'gambar_utama' => ['nullable', 'image', 'max:4096'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'max:4096'],
            'tags' => ['nullable'],
        ]);

        $slug = $this->resolveSlug($validated['slug'] ?? null, $validated['judul']);
        $publishedAt = $this->resolvePublishedAt($validated['status'], $validated['published_at'] ?? null);

        $paths = ['gambar_utama' => null, 'thumbnail' => null];
        if ($request->hasFile('gambar_utama')) {
            $paths = $this->storeImageWithThumbnail($request->file('gambar_utama'));
        }

        $berita = Berita::create([
            'user_id' => $request->user()->id,
            'kategori_berita_id' => $validated['kategori_berita_id'],
            'judul' => $validated['judul'],
            'slug' => $slug,
            'ringkasan' => $validated['ringkasan'] ?? null,
            'konten' => $this->sanitize($validated['konten'] ?? null),
            'gambar_utama' => $paths['gambar_utama'],
            'thumbnail' => $paths['thumbnail'],
            'status' => $validated['status'],
            'published_at' => $publishedAt,
            'meta_title' => ($validated['meta_title'] ?? null) ?: $validated['judul'],
            'meta_description' => ($validated['meta_description'] ?? null) ?: Str::limit(strip_tags($validated['ringkasan'] ?? $validated['konten'] ?? ''), 160),
            'views_count' => 0,
        ]);

        $this->syncTags($berita, $request->input('tags'));
        $this->storeGallery($berita, $request->file('gallery', []));

        return redirect()->route('jurnalistik.beritas.index')->with('success', 'Berita berhasil dibuat.');
    }

    public function edit(Berita $berita): View
    {
        $berita->load(['tags', 'gambars']);

        return view('jurnalistik.beritas.edit', [
            'berita' => $berita,
            'kategoris' => KategoriBerita::orderBy('nama')->get(),
            'tags' => Tag::orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, Berita $berita): RedirectResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string', 'max:500'],
            'konten' => ['nullable', 'string'],
            'kategori_berita_id' => ['required', 'exists:kategori_beritas,id'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'gambar_utama' => ['nullable', 'image', 'max:4096'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'max:4096'],
            'tags' => ['nullable'],
        ]);

        $slug = $this->resolveSlug($validated['slug'] ?? null, $validated['judul'], $berita->id);
        $publishedAt = $this->resolvePublishedAt($validated['status'], $validated['published_at'] ?? null);

        $data = [
            'kategori_berita_id' => $validated['kategori_berita_id'],
            'judul' => $validated['judul'],
            'slug' => $slug,
            'ringkasan' => $validated['ringkasan'] ?? null,
            'konten' => $this->sanitize($validated['konten'] ?? null),
            'status' => $validated['status'],
            'published_at' => $publishedAt,
            'meta_title' => ($validated['meta_title'] ?? null) ?: $validated['judul'],
            'meta_description' => ($validated['meta_description'] ?? null) ?: Str::limit(strip_tags($validated['ringkasan'] ?? $validated['konten'] ?? ''), 160),
        ];

        if ($request->hasFile('gambar_utama')) {
            if ($berita->gambar_utama) {
                Storage::disk('public')->delete($berita->gambar_utama);
            }
            if ($berita->thumbnail) {
                Storage::disk('public')->delete($berita->thumbnail);
            }
            $paths = $this->storeImageWithThumbnail($request->file('gambar_utama'));
            $data['gambar_utama'] = $paths['gambar_utama'];
            $data['thumbnail'] = $paths['thumbnail'];
        }

        $berita->update($data);

        $this->syncTags($berita, $request->input('tags'));
        $this->storeGallery($berita, $request->file('gallery', []));

        return redirect()->route('jurnalistik.beritas.index')->with('success', 'Berita berhasil diperbarui.');
    }

    public function destroy(Berita $berita): RedirectResponse
    {
        $user = request()->user();
        $isOwner = $berita->user_id === $user->id;
        $isAdmin = $user->hasRole(['admin', 'super-admin']);
        abort_unless($isOwner || $isAdmin, 403);

        $berita->delete();

        return redirect()->route('jurnalistik.beritas.index')->with('success', 'Berita dihapus.');
    }

    public function publish(Berita $berita): RedirectResponse
    {
        if ($berita->status === 'published') {
            $berita->update(['status' => 'draft', 'published_at' => null]);
        } else {
            $berita->update(['status' => 'published', 'published_at' => now()]);
        }

        return back()->with('success', 'Status berita diperbarui.');
    }

    private function resolveSlug(?string $slug, string $judul, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $judul);
        if ($base === '') {
            $base = 'berita';
        }
        $candidate = $base;
        $i = 2;
        while (Berita::where('slug', $candidate)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    private function resolvePublishedAt(string $status, ?string $publishedAt): ?Carbon
    {
        if ($status === 'draft') {
            return null;
        }
        if ($publishedAt) {
            return Carbon::parse($publishedAt);
        }

        return now();
    }

    private function syncTags(Berita $berita, $tagsInput): void
    {
        $names = [];
        if (is_array($tagsInput)) {
            $names = $tagsInput;
        } elseif (is_string($tagsInput) && $tagsInput !== '') {
            $names = explode(',', $tagsInput);
        }

        $ids = [];
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['nama' => $name]
            );
            $ids[] = $tag->id;
        }
        $berita->tags()->sync($ids);
    }

    private function storeGallery(Berita $berita, array $files): void
    {
        $urutan = $berita->gambars()->max('urutan') ?? 0;
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $path = $file->store('berita/gallery', 'public');
            $urutan++;
            BeritaGambar::create([
                'berita_id' => $berita->id,
                'path' => $path,
                'urutan' => $urutan,
            ]);
        }
    }

    private function storeImageWithThumbnail(UploadedFile $file): array
    {
        $path = $file->store('berita', 'public');
        $fullPath = Storage::disk('public')->path($path);

        $thumbnailPath = 'berita/thumbnails/'.pathinfo($path, PATHINFO_FILENAME).'_thumb.jpg';
        $thumbFull = Storage::disk('public')->path($thumbnailPath);
        @mkdir(dirname($thumbFull), 0755, true);

        $this->makeThumbnail($fullPath, $thumbFull, 400);

        return ['gambar_utama' => $path, 'thumbnail' => $thumbnailPath];
    }

    private function makeThumbnail(string $src, string $dst, int $maxWidth): void
    {
        $info = @getimagesize($src);
        if (! $info) {
            return;
        }
        [$w, $h] = $info;
        $mime = $info['mime'];
        $srcImg = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($src),
            'image/png' => @imagecreatefrompng($src),
            'image/gif' => @imagecreatefromgif($src),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : null,
            default => null,
        };
        if (! $srcImg) {
            return;
        }
        $ratio = $w > $maxWidth ? $maxWidth / $w : 1;
        $nw = (int) round($w * $ratio);
        $nh = (int) round($h * $ratio);
        $dstImg = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagejpeg($dstImg, $dst, 85);
        imagedestroy($srcImg);
        imagedestroy($dstImg);
    }

    private function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }
        // Hapus tag berbahaya beserta isinya.
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html);
        $html = preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $html);
        // Hapus tag berbahaya tunggal.
        $html = preg_replace('#</?(script|style|iframe|object|embed|form|input|button)\b[^>]*>#is', '', $html);
        // Hapus event handler on* dan javascript: uri.
        $html = preg_replace('#\son\w+\s*=\s*"[^"]*"#i', '', $html);
        $html = preg_replace("#\son\w+\s*=\s*'[^']*'#i", '', $html);
        $html = preg_replace('#javascript:#i', '', $html);

        return $html;
    }
}
