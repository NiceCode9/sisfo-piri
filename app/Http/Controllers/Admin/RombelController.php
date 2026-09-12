<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePengampuBatchRequest;
use App\Http\Requests\Admin\StoreRombelRequest;
use App\Http\Requests\Admin\UpdateRombelRequest;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RombelController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:rombels.view', only: ['index', 'show']),
            new Middleware('permission:rombels.create', only: ['create', 'store', 'salin', 'prosesSalin']),
            new Middleware('permission:rombels.edit', only: ['edit', 'update']),
            new Middleware('permission:rombels.delete', only: ['destroy']),
            new Middleware('permission:pengampus.create', only: ['storePengampuBatch']),
        ];
    }

    public function index(): View
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $tahunMode = request()->query('tahun', 'aktif');

        $rombels = Rombel::with(['kelas', 'tahunAjaran', 'waliGuru'])
            ->withCount('pengampus')
            ->when($tahunMode === 'aktif' && $tahunAktif, fn ($q) => $q->where('tahun_ajaran_id', $tahunAktif->id))
            ->when(is_numeric($tahunMode), fn ($q) => $q->where('tahun_ajaran_id', $tahunMode))
            ->when(request('search'), fn ($q, $s) => $q->whereHas('kelas', fn ($qq) => $qq->where('nama_kelas', 'like', "%{$s}%")))
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.rombels.index', [
            'rombels' => $rombels,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => $tahunAktif,
            'tahunMode' => $tahunMode,
        ]);
    }

    public function create(): View
    {
        return view('admin.rombels.create', $this->formData());
    }

    public function store(StoreRombelRequest $request): RedirectResponse
    {
        $rombel = Rombel::create($request->validated());

        return redirect()->route('admin.rombels.index')->with('success', "Rombel {$rombel->kelas->nama_kelas} {$rombel->tahunAjaran->nama_tahun_ajaran} berhasil dibentuk.");
    }

    public function show(Rombel $rombel): View
    {
        $histori = $rombel->historiLengkap();

        return view('admin.rombels.show', [
            'rombel' => $rombel->load(['kelas', 'tahunAjaran', 'waliGuru']),
            'histori' => $histori,
            'gurus' => Guru::aktif()->orderBy('nama')->get(),
            'mapels' => MataPelajaran::aktif()->orderBy('kode')->get(),
            'pengampuPerMapel' => $histori['penugasan']->keyBy('mata_pelajaran_id'),
        ]);
    }

    /**
     * Simpan penugasan se-rombel sekaligus dari section editor.
     * Baris kosong diabaikan; baris terisi di-create atau di-update gurunya.
     */
    public function storePengampuBatch(StorePengampuBatchRequest $request, Rombel $rombel): RedirectResponse
    {
        $hasil = DB::transaction(function () use ($request, $rombel) {
            $ditambah = 0;
            $diperbarui = 0;

            foreach ($request->validated()['guru'] as $mapelId => $guruId) {
                if (! $guruId) {
                    continue;
                }

                $tugas = Pengampu::updateOrCreate(
                    ['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapelId],
                    ['guru_id' => $guruId]
                );

                if ($tugas->wasRecentlyCreated) {
                    $ditambah++;
                } elseif ($tugas->wasChanged()) {
                    $diperbarui++;
                }
            }

            return compact('ditambah', 'diperbarui');
        });

        return redirect()->route('admin.rombels.show', $rombel)
            ->with('success', "Penugasan rombel {$rombel->kelas->nama_kelas} disimpan: {$hasil['ditambah']} ditambah, {$hasil['diperbarui']} diperbarui.");
    }

    public function edit(Rombel $rombel): View
    {
        return view('admin.rombels.edit', array_merge(['rombel' => $rombel], $this->formData()));
    }

    public function update(UpdateRombelRequest $request, Rombel $rombel): RedirectResponse
    {
        $rombel->update($request->validated());

        return redirect()->route('admin.rombels.index')->with('success', 'Rombel diperbarui. Histori penugasan tidak berubah.');
    }

    public function destroy(Rombel $rombel): RedirectResponse
    {
        if ($rombel->pengampus()->exists()) {
            return back()->with('error', 'Rombel masih memiliki penugasan dan tidak dapat dihapus.');
        }

        $rombel->delete();

        return redirect()->route('admin.rombels.index')->with('success', 'Rombel dihapus.');
    }

    /**
     * Form salin rombel + penugasan antar tahun ajaran.
     */
    public function salin(): View
    {
        $tahunAjarans = TahunAjaran::orderByDesc('tanggal_mulai')->get();
        $aktif = TahunAjaran::aktif()->first();
        $sebelumAktif = TahunAjaran::where('id', '!=', $aktif?->id)->orderByDesc('tanggal_mulai')->first();

        return view('admin.rombels.salin', [
            'tahunAjarans' => $tahunAjarans,
            'tahunSumber' => $sebelumAktif ?? $aktif,
            'tahunTujuan' => $aktif,
            'jumlahSumber' => $sebelumAktif ? Rombel::where('tahun_ajaran_id', $sebelumAktif->id)->count() : 0,
        ]);
    }

    /**
     * Bentuk rombel tahun tujuan dari tahun sumber beserta penugasannya.
     * Idempoten: yang sudah ada dilewati dan dilaporkan.
     */
    public function prosesSalin(Request $request): RedirectResponse
    {
        $request->validate([
            'tahun_sumber_id' => ['required', 'integer', 'exists:tahun_ajarans,id', 'different:tahun_tujuan_id'],
            'tahun_tujuan_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
        ]);

        $hasil = DB::transaction(function () use ($request) {
            $rombelDisalin = 0;
            $rombelDilewati = 0;
            $tugasDisalin = 0;
            $tugasDilewati = 0;

            $sumber = Rombel::with('pengampus')->where('tahun_ajaran_id', $request->integer('tahun_sumber_id'))->get();

            foreach ($sumber as $r) {
                $baru = Rombel::firstOrCreate(
                    ['kelas_id' => $r->kelas_id, 'tahun_ajaran_id' => $request->integer('tahun_tujuan_id')],
                    ['wali_guru_id' => $r->wali_guru_id]
                );

                $baru->wasRecentlyCreated ? $rombelDisalin++ : $rombelDilewati++;

                foreach ($r->pengampus as $p) {
                    $tugas = Pengampu::firstOrCreate(
                        ['mata_pelajaran_id' => $p->mata_pelajaran_id, 'rombel_id' => $baru->id],
                        ['guru_id' => $p->guru_id]
                    );

                    $tugas->wasRecentlyCreated ? $tugasDisalin++ : $tugasDilewati++;
                }
            }

            return compact('rombelDisalin', 'rombelDilewati', 'tugasDisalin', 'tugasDilewati');
        });

        return redirect()->route('admin.rombels.index', ['tahun' => request('tahun_tujuan_id')])
            ->with('success', "Salin selesai: {$hasil['rombelDisalin']} rombel + {$hasil['tugasDisalin']} penugasan disalin, {$hasil['rombelDilewati']} rombel + {$hasil['tugasDilewati']} penugasan dilewati (sudah ada).");
    }

    /**
     * Opsi dropdown form (kelas, tahun, guru aktif).
     */
    protected function formData(): array
    {
        return [
            'kelases' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => TahunAjaran::aktif()->first(),
            'gurus' => Guru::aktif()->orderBy('nama')->get(),
        ];
    }
}
