<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePengampuRequest;
use App\Http\Requests\Admin\UpdatePengampuRequest;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\TahunAjaran;
use App\Models\WaliKelas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PengampuController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pengampus.view', only: ['index']),
            new Middleware('permission:pengampus.create', only: ['create', 'store', 'salin', 'prosesSalin']),
            new Middleware('permission:pengampus.edit', only: ['edit', 'update']),
            new Middleware('permission:pengampus.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $pengampus = Pengampu::with(['guru', 'mataPelajaran', 'kelas', 'tahunAjaran'])
            ->when(request('tahun'), fn ($q, $t) => $q->where('tahun_ajaran_id', $t))
            ->when(request('kelas'), fn ($q, $k) => $q->where('kelas_id', $k))
            ->when(request('search'), fn ($q, $s) => $q->whereHas('guru', fn ($qq) => $qq->where('nama', 'like', "%{$s}%")))
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.pengampus.index', [
            'pengampus' => $pengampus,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'kelasList' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pengampus.create', $this->formData());
    }

    public function store(StorePengampuRequest $request): RedirectResponse
    {
        $pengampu = Pengampu::create($request->validated());

        return redirect()->route('admin.pengampus.index')->with('success', "Penugasan {$pengampu->guru->nama} ({$pengampu->mataPelajaran->kode} {$pengampu->kelas->nama_kelas}) berhasil ditambahkan.");
    }

    public function edit(Pengampu $pengampu): View
    {
        return view('admin.pengampus.edit', array_merge(['pengampu' => $pengampu], $this->formData()));
    }

    public function update(UpdatePengampuRequest $request, Pengampu $pengampu): RedirectResponse
    {
        $pengampu->update($request->validated());

        return redirect()->route('admin.pengampus.index')->with('success', 'Penugasan diperbarui. Riwayat tahun lain tidak berubah.');
    }

    public function destroy(Pengampu $pengampu): RedirectResponse
    {
        $pengampu->delete();

        return redirect()->route('admin.pengampus.index')->with('success', 'Penugasan dihapus.');
    }

    /**
     * Form salin penugasan + wali kelas antar tahun ajaran.
     */
    public function salin(): View
    {
        $tahunAjarans = TahunAjaran::orderByDesc('tanggal_mulai')->get();
        $aktif = TahunAjaran::aktif()->first();
        $sebelumAktif = TahunAjaran::where('id', '!=', $aktif?->id)->orderByDesc('tanggal_mulai')->first();

        return view('admin.pengampus.salin', [
            'tahunAjarans' => $tahunAjarans,
            'tahunSumber' => $sebelumAktif ?? $aktif,
            'tahunTujuan' => $aktif,
            'jumlahSumber' => $sebelumAktif
                ? Pengampu::where('tahun_ajaran_id', $sebelumAktif->id)->count()
                : 0,
            'jumlahWaliSumber' => $sebelumAktif
                ? WaliKelas::where('tahun_ajaran_id', $sebelumAktif->id)->count()
                : 0,
        ]);
    }

    /**
     * Salin penugasan + wali kelas ke tahun tujuan.
     * Idempoten: baris yang sudah ada dilewati dan dilaporkan.
     */
    public function prosesSalin(Request $request): RedirectResponse
    {
        $request->validate([
            'tahun_sumber_id' => ['required', 'integer', 'exists:tahun_ajarans,id', 'different:tahun_tujuan_id'],
            'tahun_tujuan_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
        ]);

        $hasil = DB::transaction(function () use ($request) {
            $disalin = 0;
            $dilewati = 0;

            foreach (Pengampu::where('tahun_ajaran_id', $request->integer('tahun_sumber_id'))->get() as $p) {
                $ada = Pengampu::firstOrCreate(
                    [
                        'mata_pelajaran_id' => $p->mata_pelajaran_id,
                        'kelas_id' => $p->kelas_id,
                        'tahun_ajaran_id' => $request->integer('tahun_tujuan_id'),
                    ],
                    ['guru_id' => $p->guru_id]
                );

                $ada->wasRecentlyCreated ? $disalin++ : $dilewati++;
            }

            $waliDisalin = 0;
            $waliDilewati = 0;

            foreach (WaliKelas::where('tahun_ajaran_id', $request->integer('tahun_sumber_id'))->get() as $w) {
                $ada = WaliKelas::firstOrCreate(
                    [
                        'kelas_id' => $w->kelas_id,
                        'tahun_ajaran_id' => $request->integer('tahun_tujuan_id'),
                    ],
                    ['guru_id' => $w->guru_id]
                );

                $ada->wasRecentlyCreated ? $waliDisalin++ : $waliDilewati++;
            }

            return compact('disalin', 'dilewati', 'waliDisalin', 'waliDilewati');
        });

        return redirect()->route('admin.pengampus.index', ['tahun' => request('tahun_tujuan_id')])
            ->with('success', "Salin selesai: {$hasil['disalin']} penugasan + {$hasil['waliDisalin']} wali disalin, {$hasil['dilewati']} penugasan + {$hasil['waliDilewati']} wali dilewati (sudah ada).");
    }

    /**
     * Opsi dropdown form (guru/mapel/kelas aktif + semua tahun).
     */
    protected function formData(): array
    {
        return [
            'gurus' => Guru::aktif()->orderBy('nama')->get(),
            'mapels' => MataPelajaran::aktif()->orderBy('kode')->get(),
            'kelasList' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => TahunAjaran::aktif()->first(),
        ];
    }
}
