<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class SitemapController extends Controller
{
    public function robots(): Response
    {
        $sitemap = url('/sitemap.xml');

        $body = "User-agent: *\n"
            ."Allow: /\n"
            ."Disallow: /admin/\n"
            ."Disallow: /siswa/\n"
            ."Disallow: /guru/\n"
            ."Disallow: /ortu/\n"
            ."Disallow: /login\n"
            ."Disallow: /logout\n"
            ."Disallow: /dokumen/\n"
            ."\n"
            ."Sitemap: {$sitemap}\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function index(): Response
    {
        $urls = [];

        $add = function (string $loc, ?string $lastmod = null, string $changefreq = 'weekly', string $priority = '0.8') use (&$urls): void {
            $urls[] = [
                'loc' => $loc,
                'lastmod' => $lastmod,
                'changefreq' => $changefreq,
                'priority' => $priority,
            ];
        };

        $add(route('spmb.home'), null, 'daily', '1.0');
        $add(route('spmb.pendaftaran'), null, 'weekly', '0.9');
        $add(route('spmb.about'), null, 'monthly', '0.7');
        $add(route('spmb.pengumuman.index'), null, 'daily', '0.8');

        if (Schema::hasTable('pengumuman')) {
            foreach (Pengumuman::where('status_aktif', true)->latest('tanggal_pengumuman')->get() as $p) {
                $add(route('spmb.pengumuman.show', $p), optional($p->updated_at)->toAtomString(), 'monthly', '0.6');
            }
        }

        $xml = view('seo.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
