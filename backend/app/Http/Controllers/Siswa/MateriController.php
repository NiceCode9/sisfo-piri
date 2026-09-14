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
        $rombels = $siswa ? Rombel::where('kelas_id', $siswa->kelas_id)->where('tahun_ajaran_id', $siswa->tahun_ajaran_id)->pluck('id') : collect();

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
        abort_unless($siswa && $materi->rombel_id === Rombel::where('kelas_id', $siswa->kelas_id)->where('tahun_ajaran_id', $siswa->tahun_ajaran_id)->first()?->id, 403);

        $materi->load(['mataPelajaran', 'guru', 'rombel.kelas']);

        return view('siswa.materi.show', compact('materi'));
    }
}
