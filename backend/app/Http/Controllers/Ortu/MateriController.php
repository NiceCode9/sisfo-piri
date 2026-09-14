<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\WaliMurid;
use Illuminate\View\View;

class MateriController extends Controller
{
    public function index(): View
    {
        $anakIds = WaliMurid::where('user_id', auth()->id())->pluck('siswa_id');
        $rombels = Siswa::whereIn('id', $anakIds)->get()->map(fn ($s) => Rombel::where('kelas_id', $s->kelas_id)->where('tahun_ajaran_id', $s->tahun_ajaran_id)->first()?->id)->filter();

        $materis = Materi::with(['mataPelajaran', 'guru', 'rombel.kelas'])
            ->whereIn('rombel_id', $rombels)
            ->where('is_aktif', true)
            ->latest()
            ->paginate(10);

        return view('ortu.materi.index', compact('materis'));
    }

    public function show(Materi $materi): View
    {
        $anakIds = WaliMurid::where('user_id', auth()->id())->pluck('siswa_id');
        $rombels = Siswa::whereIn('id', $anakIds)->get()->map(fn ($s) => Rombel::where('kelas_id', $s->kelas_id)->where('tahun_ajaran_id', $s->tahun_ajaran_id)->first()?->id)->filter();
        abort_unless($rombels->contains($materi->rombel_id), 403);

        $materi->load(['mataPelajaran', 'guru', 'rombel.kelas']);

        return view('ortu.materi.show', compact('materi'));
    }
}
