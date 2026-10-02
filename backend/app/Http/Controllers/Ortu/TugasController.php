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
    /**
     * ID anak-anak milik wali murid yang sedang login.
     *
     * @return array<int>
     */
    protected function anakIds(): array
    {
        return WaliMurid::where('user_id', auth()->id())->pluck('siswa_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Rombel anak-anak, dirangkum dari kelas + tahun berjalan masing-masing.
     *
     * @return array<int>
     */
    protected function rombelAnakIds(): array
    {
        $anakIds = $this->anakIds();

        return Siswa::whereIn('id', $anakIds)->get()
            ->flatMap(fn (Siswa $s) => Rombel::untukSiswa($s))
            ->unique()
            ->values()
            ->all();
    }

    public function index(): View
    {
        $tugas = Tugas::with(['mataPelajaran', 'rombel.kelas'])
            ->whereIn('rombel_id', $this->rombelAnakIds())
            ->where('is_aktif', true)
            ->latest()
            ->paginate(10);

        return view('ortu.tugas.index', compact('tugas'));
    }

    public function show(Tugas $tugas): View
    {
        abort_unless(in_array($tugas->rombel_id, $this->rombelAnakIds(), true), 403);

        $tugas->load(['mataPelajaran', 'rombel.kelas']);

        // Hanya pengumpulan milik anak sendiri. Versi lama memuat relasi
        // `pengumpulans` tanpa filter, sehingga halaman ini menampilkan
        // nama, berkas, nilai, dan catatan guru milik seluruh teman
        // sekelas anak.
        $pengumpulans = PengumpulanTugas::where('tugas_id', $tugas->id)
            ->whereIn('siswa_id', $this->anakIds())
            ->with('siswa.user')
            ->latest()
            ->get();

        return view('ortu.tugas.show', compact('tugas', 'pengumpulans'));
    }

    public function rekap(): View
    {
        $anakIds = $this->anakIds();
        $anak = Siswa::with('user')->whereIn('id', $anakIds)->get();
        $rekap = [];
        foreach ($anak as $siswa) {
            $pengumpulans = PengumpulanTugas::with('tugas')->where('siswa_id', $siswa->id)->get();
            $rekap[$siswa->id] = ['siswa' => $siswa, 'rata' => $pengumpulans->whereNotNull('nilai')->avg('nilai'), 'jumlah' => $pengumpulans->count()];
        }

        return view('ortu.tugas.rekap', compact('rekap'));
    }
}
