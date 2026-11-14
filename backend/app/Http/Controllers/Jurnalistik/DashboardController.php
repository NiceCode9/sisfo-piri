<?php

namespace App\Http\Controllers\Jurnalistik;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use App\Models\BeritaView;
use App\Models\KategoriBerita;
use App\Models\Komentar;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalViews = BeritaView::count();
        $uniqueVisitors = BeritaView::distinct('ip_hash')->count('ip_hash');
        $avgDuration = (int) round(BeritaView::where('duration_seconds', '>', 0)->avg('duration_seconds') ?? 0);

        // Top 5 berita by views (last 30 days)
        $topBerita = Berita::withCount(['views' => fn ($q) => $q->where('created_at', '>=', now()->subDays(30))])
            ->orderByDesc('views_count')
            ->limit(5)
            ->get();

        // Views per day (last 7 days) for simple trend
        $viewsPerDay = BeritaView::select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('c', 'd');

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
            'totalViews' => $totalViews,
            'uniqueVisitors' => $uniqueVisitors,
            'avgDuration' => $avgDuration,
            'topBerita' => $topBerita,
            'viewsPerDay' => $viewsPerDay,
        ]);
    }
}
