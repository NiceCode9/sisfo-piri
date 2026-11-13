<?php

namespace App\Http\Controllers\Jurnalistik;

use App\Http\Controllers\Controller;
use App\Models\Komentar;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KomentarController extends Controller
{
    public function index(): View
    {
        $komentars = Komentar::with('berita')->latest()->paginate(15);

        return view('jurnalistik.komentars.index', compact('komentars'));
    }

    public function approve(Komentar $komentar): RedirectResponse
    {
        $komentar->update(['status' => 'approved']);

        return back()->with('success', 'Komentar disetujui.');
    }

    public function reject(Komentar $komentar): RedirectResponse
    {
        $komentar->update(['status' => 'rejected']);

        return back()->with('success', 'Komentar ditolak.');
    }

    public function destroy(Komentar $komentar): RedirectResponse
    {
        $komentar->delete();

        return back()->with('success', 'Komentar dihapus.');
    }
}
