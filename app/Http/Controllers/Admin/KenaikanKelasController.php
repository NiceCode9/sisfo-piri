<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProsesKenaikanWizardRequest;
use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
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

            $tingkatAkhir = Kelas::pluck('tingkat')->map(fn ($t) => (int) $t)->max();
            $semuaKelas = Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get();

            $grup = [];
            $petaOtomatis = [];

            foreach ($siswas->groupBy('kelas_id') as $kelasId => $anggota) {
                $kelas = $anggota->first()->kelas;
                $grup[$kelasId] = ['kelas' => $kelas, 'siswas' => $anggota];

                if ((int) $kelas->tingkat === $tingkatAkhir) {
                    $petaOtomatis[$kelasId] = 'LULUS';
                } else {
                    // Pasangan = tingkat+1 dengan huruf yang sama (7A→8A).
                    $huruf = preg_replace('/^\d+/', '', (string) $kelas->nama_kelas);
                    $pasangan = $semuaKelas->first(fn ($k) => (int) $k->tingkat === (int) $kelas->tingkat + 1
                        && preg_replace('/^\d+/', '', (string) $k->nama_kelas) === $huruf);
                    $petaOtomatis[$kelasId] = $pasangan?->id;
                }
            }

            $data['grup'] = $grup;
            $data['petaOtomatis'] = $petaOtomatis;
            $data['tingkatAkhir'] = $tingkatAkhir;
        }

        return view('admin.kenaikan-kelas.index', $data);
    }

    /**
     * Eksekusi wizard: naik / tinggal / lulus sekaligus.
     * Idempoten: yang sudah punya riwayat tahun tujuan dilewati.
     */
    public function proses(ProsesKenaikanWizardRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $pemetaan = $validated['pemetaan'];
        $override = $validated['override'] ?? [];

        $laporan = DB::transaction(function () use ($validated, $pemetaan, $override) {
            $rinci = [];
            $dilewati = 0;

            $siswas = Siswa::where('is_aktif', true)
                ->where('tahun_ajaran_id', $validated['tahun_asal_id'])
                ->lockForUpdate()
                ->get();

            foreach ($siswas as $siswa) {
                if (RiwayatKelas::where('siswa_id', $siswa->id)
                    ->where('tahun_ajaran_id', $validated['tahun_tujuan_id'])
                    ->exists()
                ) {
                    $dilewati++;

                    continue;
                }

                $aksi = $override[$siswa->id] ?? 'ikuti';
                $tujuan = $pemetaan[$siswa->kelas_id] ?? '';

                if ($aksi === 'ikuti') {
                    if ($tujuan === 'LULUS') {
                        $aksi = 'lulus';
                    } elseif ($tujuan) {
                        $aksi = 'naik';
                    } else {
                        $dilewati++;

                        continue;
                    }
                }

                if ($aksi === 'naik') {
                    RiwayatKelas::create([
                        'siswa_id' => $siswa->id,
                        'kelas_id' => $tujuan,
                        'tahun_ajaran_id' => $validated['tahun_tujuan_id'],
                        'status' => 'aktif',
                    ]);
                    $siswa->update([
                        'kelas_id' => $tujuan,
                        'tahun_ajaran_id' => $validated['tahun_tujuan_id'],
                    ]);
                } elseif ($aksi === 'tinggal') {
                    RiwayatKelas::create([
                        'siswa_id' => $siswa->id,
                        'kelas_id' => $siswa->kelas_id,
                        'tahun_ajaran_id' => $validated['tahun_tujuan_id'],
                        'status' => 'mengulang',
                    ]);
                    $siswa->update(['tahun_ajaran_id' => $validated['tahun_tujuan_id']]);
                } else {
                    RiwayatKelas::create([
                        'siswa_id' => $siswa->id,
                        'kelas_id' => $siswa->kelas_id,
                        'tahun_ajaran_id' => $validated['tahun_tujuan_id'],
                        'status' => 'lulus',
                    ]);
                    $siswa->update(['is_aktif' => false]);
                }

                $kunci = $siswa->kelas->nama_kelas ?? '?';
                $rinci[$kunci] ??= ['naik' => 0, 'tinggal' => 0, 'lulus' => 0];
                $rinci[$kunci][$aksi]++;
            }

            return ['rinci' => $rinci, 'dilewati' => $dilewati];
        });

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
