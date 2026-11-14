<?php

namespace App\Http\Controllers\Jurnalistik;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use App\Models\KategoriBerita;
use App\Models\Komentar;
use App\Models\Tag;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('jurnalistik.dashboard', [
            'totalBerita' => Berita::count(),
            'draft' => Berita::draft()->count(),
            'published' => Berita::published()->count(),
            'scheduled' => Berita::scheduled()->count(),
            'totalKategori' => KategoriBerita::count(),
            'totalTag' => Tag::count(),
            'komentarPending' => Komentar::pending()->count(),
            'beritaTerbaru' => Berita::with('kategori')->latest()->limit(5)->get(),
            'komentarTerbaru' => Komentar::with('berita')->pending()->latest()->limit(5)->get(),
        ]);
    }
}
