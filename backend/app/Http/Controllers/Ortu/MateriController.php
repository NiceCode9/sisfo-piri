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
    /**
     * @return array<int>
     */
    protected function anakIds(): array
    {
        return WaliMurid::where('user_id', auth()->id())->pluck('siswa_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<int>
     */
    protected function rombelAnakIds(): array
    {
        return Siswa::whereIn('id', $this->anakIds())->get()
            ->flatMap(fn (Siswa $s) => Rombel::untukSiswa($s))
            ->unique()
            ->values()
            ->all();
    }

    public function index(): View
    {
        $materis = Materi::with(['mataPelajaran', 'guru', 'rombel.kelas'])
            ->whereIn('rombel_id', $this->rombelAnakIds())
            ->where('is_aktif', true)
            ->latest()
            ->paginate(10);

        return view('ortu.materi.index', compact('materis'));
    }

    public function show(Materi $materi): View
    {
        abort_unless(in_array($materi->rombel_id, $this->rombelAnakIds(), true), 403);
        abort_unless($materi->is_aktif, 404);

        $materi->load(['mataPelajaran', 'guru', 'rombel.kelas']);

        return view('ortu.materi.show', compact('materi'));
    }
}
