<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProsesKenaikanRequest;
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
            new Middleware('permission:kenaikan-kelas.execute', only: ['naikkan', 'luluskan']),
        ];
    }

    /**
     * Daftar siswa aktif per kelas+tahun untuk diproses.
     */
    public function index(): View
    {
        $siswas = Siswa::with(['kelas', 'tahunAjaran', 'user', 'calonSiswa'])
            ->where('is_aktif', true)
            ->when(request('tahun'), fn ($q, $t) => $q->where('tahun_ajaran_id', $t))
            ->when(request('kelas'), fn ($q, $k) => $q->where('kelas_id', $k))
            ->orderBy('kelas_id')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.kenaikan-kelas.index', [
            'siswas' => $siswas,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => TahunAjaran::aktif()->first(),
            'kelasList' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
        ]);
    }

    /**
     * Naikkan siswa terpilih ke kelas+tahun tujuan.
     * Baris riwayat yang sudah ada dilewati (idempoten).
     */
    public function naikkan(ProsesKenaikanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (empty($validated['kelas_tujuan_id'])) {
            return back()->with('error', 'Pilih kelas tujuan dulu.')->withInput();
        }

        $diproses = DB::transaction(function () use ($validated) {
            $count = 0;

            foreach (Siswa::whereIn('id', $validated['siswa_ids'])->lockForUpdate()->get() as $siswa) {
                $sudah = RiwayatKelas::where('siswa_id', $siswa->id)
                    ->where('tahun_ajaran_id', $validated['tahun_tujuan_id'])
                    ->exists();

                if ($sudah) {
                    continue;
                }

                RiwayatKelas::create([
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $validated['kelas_tujuan_id'],
                    'tahun_ajaran_id' => $validated['tahun_tujuan_id'],
                    'status' => 'aktif',
                ]);

                $siswa->update([
                    'kelas_id' => $validated['kelas_tujuan_id'],
                    'tahun_ajaran_id' => $validated['tahun_tujuan_id'],
                ]);

                $count++;
            }

            return $count;
        });

        return back()->with('success', "{$diproses} siswa dinaikkan (yang sudah punya riwayat dilewati).");
    }

    /**
     * Luluskan siswa terpilih: riwayat status lulus + nonaktifkan akun siswa.
     */
    public function luluskan(ProsesKenaikanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $diproses = DB::transaction(function () use ($validated) {
            $count = 0;

            foreach (Siswa::whereIn('id', $validated['siswa_ids'])->lockForUpdate()->get() as $siswa) {
                $sudah = RiwayatKelas::where('siswa_id', $siswa->id)
                    ->where('tahun_ajaran_id', $validated['tahun_tujuan_id'])
                    ->exists();

                if ($sudah) {
                    continue;
                }

                RiwayatKelas::create([
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $siswa->kelas_id,
                    'tahun_ajaran_id' => $validated['tahun_tujuan_id'],
                    'status' => 'lulus',
                ]);

                $siswa->update(['is_aktif' => false]);

                $count++;
            }

            return $count;
        });

        return back()->with('success', "{$diproses} siswa diluluskan.");
    }
}
