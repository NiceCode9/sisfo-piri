<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Siswa\ProsesKenaikanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProsesKenaikanWizardRequest;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class KenaikanKelasController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:kenaikan-kelas.view', only: ['index']),
            new Middleware('permission:kenaikan-kelas.execute', only: ['proses']),
        ];
    }

    /**
     * Wizard: langkah 1 periode, langkah 2 pemetaan + pratinjau.
     */
    public function index(): View
    {
        $tahunAjarans = TahunAjaran::orderByDesc('tanggal_mulai')->get();
        $tahunAktif = TahunAjaran::aktif()->first();

        $tahunAsalId = request()->query('tahun_asal_id', $tahunAktif?->id);
        $tahunTujuanId = request()->query(
            'tahun_tujuan_id',
            $tahunAjarans->where('id', '!=', (int) $tahunAsalId)->first()?->id
        );

        $data = [
            'tahunAjarans' => $tahunAjarans,
            'tahunAktif' => $tahunAktif,
            'tahunAsalId' => $tahunAsalId ? (int) $tahunAsalId : null,
            'tahunTujuanId' => $tahunTujuanId ? (int) $tahunTujuanId : null,
            'kelasList' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
        ];

        if ($data['tahunAsalId'] && $data['tahunTujuanId'] && $data['tahunAsalId'] !== $data['tahunTujuanId']) {
            $siswas = Siswa::with(['kelas', 'user', 'calonSiswa'])
                ->where('is_aktif', true)
                ->where('tahun_ajaran_id', $data['tahunAsalId'])
                ->orderBy('kelas_id')
                ->orderBy('nis')
                ->get();

            $grup = [];
            $petaOtomatis = app(ProsesKenaikanAction::class)->petaOtomatis();

            foreach ($siswas->groupBy('kelas_id') as $kelasId => $anggota) {
                $grup[$kelasId] = ['kelas' => $anggota->first()->kelas, 'siswas' => $anggota];
            }

            $data['grup'] = $grup;
            $data['petaOtomatis'] = $petaOtomatis;
            $data['tingkatAkhir'] = (int) Kelas::pluck('tingkat')->map(fn ($t) => (int) $t)->max();
        }

        return view('admin.kenaikan-kelas.index', $data);
    }

    /**
     * Eksekusi wizard: naik / tinggal / lulus sekaligus.
     * Idempoten: yang sudah punya riwayat tahun tujuan dilewati.
     */
    public function proses(ProsesKenaikanWizardRequest $request, ProsesKenaikanAction $kenaikan): RedirectResponse
    {
        $validated = $request->validated();
        $pemetaan = $validated['pemetaan'];
        $override = $validated['override'] ?? [];

        $laporan = $kenaikan->jalankan(
            $validated['tahun_asal_id'],
            $validated['tahun_tujuan_id'],
            $pemetaan,
            $override
        );

        $bagian = [];
        foreach ($laporan['rinci'] as $nama => $hitung) {
            $fragmen = [];
            if ($hitung['naik']) {
                $fragmen[] = "{$hitung['naik']} naik";
            }
            if ($hitung['tinggal']) {
                $fragmen[] = "{$hitung['tinggal']} tinggal";
            }
            if ($hitung['lulus']) {
                $fragmen[] = "{$hitung['lulus']} lulus";
            }
            $bagian[] = "{$nama}: ".implode(', ', $fragmen);
        }
        if ($laporan['dilewati']) {
            $bagian[] = "{$laporan['dilewati']} dilewati (sudah ada riwayat/tanpa tujuan)";
        }

        return redirect()->route('admin.kenaikan.index', [
            'tahun_asal_id' => $validated['tahun_asal_id'],
            'tahun_tujuan_id' => $validated['tahun_tujuan_id'],
        ])->with('success', 'Kenaikan diproses. '.implode('; ', $bagian).'.');
    }
}
