<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TugasRekapExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTugasRequest;
use App\Http\Requests\Admin\UpdateTugasRequest;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TugasController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:tugas.view', only: ['index', 'show', 'rekap', 'exportExcel', 'exportPdf']),
            new Middleware('permission:tugas.create', only: ['create', 'store']),
            new Middleware('permission:tugas.edit', only: ['edit', 'update']),
            new Middleware('permission:tugas.delete', only: ['destroy']),
            new Middleware('permission:tugas.nilai', only: ['nilai', 'simpanNilai']),
        ];
    }

    public function index(): View
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $tahunMode = request()->query('tahun', 'aktif');

        $tugas = Tugas::with(['rombel.kelas', 'rombel.tahunAjaran', 'mataPelajaran', 'guru'])
            ->whereHas('rombel', fn ($qq) => $qq->terjangkauUser(request()->user()))
            ->when($tahunMode === 'aktif' && $tahunAktif, fn ($q) => $q->whereHas('rombel', fn ($qq) => $qq->where('tahun_ajaran_id', $tahunAktif->id)))
            ->when(is_numeric($tahunMode), fn ($q) => $q->whereHas('rombel', fn ($qq) => $qq->where('tahun_ajaran_id', $tahunMode)))
            ->when(request('rombel'), fn ($q, $v) => $q->where('rombel_id', $v))
            ->when(request('search'), fn ($q, $s) => $q->where('judul', 'like', "%{$s}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.tugas.index', [
            'tugas' => $tugas,
            'rombels' => $this->rombels(),
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => $tahunAktif,
            'tahunMode' => $tahunMode,
        ]);
    }

    public function create(): View
    {
        return view('admin.tugas.create', $this->formData());
    }

    public function store(StoreTugasRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Admin tidak punya baris `gurus`, jadi guru_id sengaja dibiarkan null
        // daripada diisi wali rombel: atribusi harus menunjuk orang yang
        // benar-benar membuat tugas, bukan pengganti yang menyesatkan.
        $validated['guru_id'] = Guru::where('user_id', $request->user()->id)->first()?->id;
        $validated['is_aktif'] = $request->boolean('is_aktif', true);
        $tugas = Tugas::create($validated);

        return redirect()->route('admin.tugas.index')->with('success', "Tugas {$tugas->judul} ditambahkan.");
    }

    public function show(Tugas $tuga): View
    {
        $this->authorize('view', $tuga);

        $tuga->load(['rombel.kelas', 'rombel.tahunAjaran', 'mataPelajaran', 'guru', 'pengumpulans.siswa.user']);

        return view('admin.tugas.show', ['tugas' => $tuga]);
    }

    public function edit(Tugas $tuga): View
    {
        $this->authorize('update', $tuga);

        return view('admin.tugas.edit', array_merge(['tugas' => $tuga], $this->formData()));
    }

    public function update(UpdateTugasRequest $request, Tugas $tuga): RedirectResponse
    {
        $this->authorize('update', $tuga);

        $validated = $request->validated();
        $validated['is_aktif'] = $request->boolean('is_aktif');
        $tuga->update($validated);

        return redirect()->route('admin.tugas.index')->with('success', 'Tugas diperbarui.');
    }

    public function destroy(Tugas $tuga): RedirectResponse
    {
        $this->authorize('delete', $tuga);

        $tuga->delete();

        return redirect()->route('admin.tugas.index')->with('success', 'Tugas dihapus.');
    }

    public function nilai(Tugas $tuga): View
    {
        $this->authorize('nilai', $tuga);

        $tuga->load(['rombel', 'mataPelajaran']);
        $pengumpulans = $tuga->pengumpulans()->with('siswa.user')->latest()->get();

        return view('admin.tugas.nilai', ['tugas' => $tuga, 'pengumpulans' => $pengumpulans]);
    }

    public function simpanNilai(Request $request, Tugas $tuga): RedirectResponse
    {
        $this->authorize('nilai', $tuga);

        $request->validate([
            'nilai' => ['required', 'array'],
            'nilai.*' => ['nullable', 'integer', 'min:0', 'max:100'],
            'catatan_guru' => ['nullable', 'array'],
            'catatan_guru.*' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($request->input('nilai', []) as $id => $nilai) {
            $pengumpulan = PengumpulanTugas::where('id', $id)->where('tugas_id', $tuga->id)->first();

            if (! $pengumpulan) {
                continue;
            }

            $pengumpulan->update([
                'nilai' => $nilai === '' ? null : $nilai,
                'catatan_guru' => $request->input("catatan_guru.{$id}"),
            ]);
        }

        return redirect()->route('admin.tugas.show', $tuga)->with('success', 'Nilai disimpan.');
    }

    /**
     * Rekap nilai harian per siswa: matriks siswa x tugas.
     */
    public function rekap(): View
    {
        ['semua' => $rombels, 'terkunci' => $terkunci] = $this->rombelTerjangkau();

        $rombelId = request()->query('rombel_id', $rombels->first()?->id);
        $mapelId = request()->query('mapel_id');

        $rombel = $rombels->firstWhere('id', (int) $rombelId);

        if ($terkunci && $rombel && ! $rombels->contains('id', $rombel->id)) {
            abort(403);
        }

        $rekap = null;

        if ($rombel) {
            $rekap = $this->dataRekap($rombel->id, $mapelId ? (int) $mapelId : null);
        }

        return view('admin.tugas.rekap', [
            'rombels' => $rombels,
            'terkunci' => $terkunci,
            'rombel' => $rombel,
            'mapels' => MataPelajaran::aktif()->orderBy('kode')->get(),
            'mapelId' => $mapelId,
            'rekap' => $rekap,
        ]);
    }

    public function exportExcel(): BinaryFileResponse
    {
        $rekap = $this->rekapTerfilter();

        return Excel::download(
            new TugasRekapExport($rekap),
            'rekap-tugas-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function exportPdf(): Response
    {
        $rekap = $this->rekapTerfilter();

        return Pdf::loadView('admin.tugas.pdf', compact('rekap'))
            ->download('rekap-tugas-'.now()->format('Ymd-His').'.pdf');
    }

    protected function rekapTerfilter(): array
    {
        ['semua' => $rombels] = $this->rombelTerjangkau();
        $rombelId = (int) request('rombel_id', $rombels->first()?->id);
        $mapelId = request('mapel_id') ? (int) request('mapel_id') : null;
        $rombel = $rombels->firstWhere('id', $rombelId);
        abort_unless($rombel, 404);

        return $this->dataRekap($rombel->id, $mapelId);
    }

    /**
     * @return array{rombel: Rombel, tugasList: Collection, siswas: Collection, matriks: array, rataPerSiswa: array}
     */
    public function dataRekap(int $rombelId, ?int $mapelId = null): array
    {
        $rombel = Rombel::with(['kelas', 'tahunAjaran'])->findOrFail($rombelId);

        $tugasList = Tugas::where('rombel_id', $rombel->id)
            ->when($mapelId, fn ($q) => $q->where('mata_pelajaran_id', $mapelId))
            ->orderBy('deadline')->orderBy('id')->get();

        $siswas = Siswa::with('user')
            ->whereIn('id', $rombel->anggotaIds())
            ->orderBy('nis')->get();

        $pengumpulans = PengumpulanTugas::whereIn('tugas_id', $tugasList->pluck('id'))
            ->whereIn('siswa_id', $siswas->pluck('id'))
            ->get()
            ->groupBy(fn ($p) => $p->siswa_id.'-'.$p->tugas_id);

        $matriks = [];
        $rataPerSiswa = [];

        foreach ($siswas as $siswa) {
            $row = [];
            $total = 0;
            $count = 0;

            foreach ($tugasList as $tugas) {
                $key = $siswa->id.'-'.$tugas->id;
                $p = $pengumpulans->get($key)?->first();
                $row[$tugas->id] = $p;
                if ($p?->nilai !== null) {
                    $total += $p->nilai;
                    $count++;
                }
            }

            $matriks[$siswa->id] = $row;
            $rataPerSiswa[$siswa->id] = $count ? round($total / $count, 1) : null;
        }

        return compact('rombel', 'tugasList', 'siswas', 'matriks', 'rataPerSiswa', 'mapelId');
    }

    /**
     * Rombel milik user yang sedang login, untuk dropdown dan rekap.
     *
     * Versi lama mengembalikan SELURUH rombel bila user tidak punya kelas wali
     * (termasuk user yang bukan guru sama sekali), sehingga rekap nilai dan
     * dropdown kelas bisa dibuka untuk kelas milik guru lain. Sekarang
     * kekosongan tidak lagi berarti "buka semua".
     *
     * @return array{semua: Collection, terkunci: bool}
     */
    protected function rombelTerjangkau(): array
    {
        return [
            'semua' => $this->rombels(),
            'terkunci' => ! request()->user()->hasRole(Rombel::ROLE_UNIVERSAL),
        ];
    }

    /**
     * Rombel yang boleh dikelola user, terurut dari tahun ajaran aktif.
     *
     * @return Collection<int, Rombel>
     */
    protected function rombels(): Collection
    {
        $tahunAktif = TahunAjaran::aktif()->first();

        return Rombel::with(['kelas', 'tahunAjaran'])
            ->terjangkauUser(request()->user())
            ->when($tahunAktif, fn ($q) => $q->orderByRaw('tahun_ajaran_id = ? desc', [$tahunAktif->id]))
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->get();
    }

    protected function formData(): array
    {
        return [
            'rombels' => $this->rombels(),
            'mapels' => MataPelajaran::aktif()->orderBy('kode')->get(),
        ];
    }
}
