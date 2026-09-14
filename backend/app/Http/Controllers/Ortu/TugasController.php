<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\PengumpulanTugas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Models\WaliMurid;
use Illuminate\View\View;

class TugasController extends Controller
{
    public function index(): View
    {
        $anakIds = WaliMurid::where('user_id', auth()->id())->pluck('siswa_id');
        $rombels = Siswa::whereIn('id', $anakIds)->get()->map(fn ($s) => Rombel::where('kelas_id', $s->kelas_id)->where('tahun_ajaran_id', $s->tahun_ajaran_id)->first()?->id)->filter();

        $tugas = Tugas::with(['mataPelajaran', 'rombel.kelas'])
            ->whereIn('rombel_id', $rombels)
            ->where('is_aktif', true)
            ->latest()
            ->paginate(10);

        return view('ortu.tugas.index', compact('tugas'));
    }

    public function show(Tugas $tugas): View
    {
        $anakIds = WaliMurid::where('user_id', auth()->id())->pluck('siswa_id');
        $rombels = Siswa::whereIn('id', $anakIds)->get()->map(fn ($s) => Rombel::where('kelas_id', $s->kelas_id)->where('tahun_ajaran_id', $s->tahun_ajaran_id)->first()?->id)->filter();
        abort_unless($rombels->contains($tugas->rombel_id), 403);

        $tugas->load(['mataPelajaran', 'rombel.kelas', 'pengumpulans.siswa.user']);

        return view('ortu.tugas.show', compact('tugas'));
    }

    public function rekap(): View
    {
        $anakIds = WaliMurid::where('user_id', auth()->id())->pluck('siswa_id');
        $anak = Siswa::with('user')->whereIn('id', $anakIds)->get();
        $rekap = [];
        foreach ($anak as $siswa) {
            $pengumpulans = PengumpulanTugas::with('tugas')->where('siswa_id', $siswa->id)->get();
            $rekap[$siswa->id] = ['siswa' => $siswa, 'rata' => $pengumpulans->whereNotNull('nilai')->avg('nilai'), 'jumlah' => $pengumpulans->count()];
        }

        return view('ortu.tugas.rekap', compact('rekap'));
    }
}
