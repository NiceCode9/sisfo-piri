<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Controller;
use App\Models\ExamQuestion;
use App\Support\PaketSoal;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan gambar dari paket import yang sedang direview.
 *
 * Tiga lapis proteksi, karena berkas di sini belum jadi soal dan belum punya
 * baris yang bisa diperiksa lewat ExamPolicy:
 *
 * 1. `signed` — URL punya tanda tangan yang kedaluwarsa 30 menit. Nama berkas
 *    di URL tidak bisa ditebak maupun diganti.
 * 2. `auth` + `cbt.manage` — hanya guru yang masuk.
 * Nama berkas divalidasi ulang di sini memakai pola yang sama dengan saat
 * ekstraksi (PaketSoal::POLA_NAMA). URL yang tanda tangannya sah pun tidak
 * bisa menunjuk berkas lain.
 */
class SoalImportPreviewController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [new Middleware('signed'), new Middleware('permission:cbt.manage')];
    }

    public function __invoke(Request $request, string $token, string $berkas): StreamedResponse
    {
        // Nama berkas selalu diacak saat ekstraksi; validasi ulang memakai pola yang
        // sama denganEkstraksi supaya tidak ada dua definisi yang bisa berbeda.
        abort_unless(PaketSoal::namaBerkasSah($berkas), 404);

        $paket = new PaketSoal($token, ExamQuestion::IMPORT_DIR.$token.'/');
        $path = $paket->berkas.$berkas;

        abort_unless(Storage::disk(ExamQuestion::DISK)->exists($path), 404);

        return Storage::disk(ExamQuestion::DISK)->response($path);
    }
}
