<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Rombel;
use Illuminate\View\View;

class MateriController extends Controller
{
    public function index(): View
    {
        $siswa = auth()->user()->siswa;
        $rombels = Rombel::untukSiswa($siswa);

        $materis = Materi::with(['mataPelajaran', 'guru', 'rombel.kelas'])
            ->whereIn('rombel_id', $rombels)
            ->where('is_aktif', true)
            ->latest()
            ->paginate(10);

        return view('siswa.materi.index', compact('materis'));
    }

    public function show(Materi $materi): View
    {
        $siswa = auth()->user()->siswa;

        abort_unless($siswa && in_array($materi->rombel_id, Rombel::untukSiswa($siswa), true), 403);
        abort_unless($materi->is_aktif, 404);

        $materi->load(['mataPelajaran', 'guru', 'rombel.kelas']);

        return view('siswa.materi.show', compact('materi'));
    }
}
