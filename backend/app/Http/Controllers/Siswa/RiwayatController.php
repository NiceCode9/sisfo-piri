<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Siswa\AbsensiSiswaRequest;
use App\Models\Absensi;
use App\Models\TahunAjaran;
use App\Services\RiwayatSiswa;
use Illuminate\View\View;

class RiwayatController extends Controller
{
    public function kelas(): View
    {
        $siswa = auth()->user()->siswa()->with(['riwayatKelas.kelas', 'riwayatKelas.tahunAjaran'])->first();

        return view('siswa.kelas', [
            'riwayat' => $siswa?->riwayatKelas->sortByDesc('created_at') ?? collect(),
        ]);
    }

    /**
     * Riwayat terpadu: kehadiran, e-learning, dan nilai CBT per rombel.
     *
     * Halaman ini menggantikan kebutuhan membuka tiga rekap terpisah hanya untuk
     * melihat satu potret murid. Read-only, bukan rapor.
     */
    public function riwayat(): View
    {
        $siswa = auth()->user()->siswa()->first();

        $baris = $siswa ? RiwayatSiswa::untukSiswa($siswa) : [];

        return view('siswa.riwayat', [
            'siswa' => $siswa,
            'baris' => $baris,
            'ringkas' => RiwayatSiswa::ringkas($baris),
        ]);
    }

    public function absensi(AbsensiSiswaRequest $request): View
    {
        $siswa = auth()->user()->siswa;
        $bulan = $request->query('bulan', now()->format('Y-m'));

        $riwayat = collect();
        $rekapBulan = [];
        $rekapTahun = [];

        if ($siswa) {
            $riwayat = Absensi::with('rombel.kelas')
                ->where('siswa_id', $siswa->id)
                ->where('tanggal', 'like', $bulan.'%')
                ->orderByDesc('tanggal')
                ->get();

            $rekapBulan = Absensi::where('siswa_id', $siswa->id)
                ->where('tanggal', 'like', $bulan.'%')
                ->selectRaw('status, COUNT(DISTINCT tanggal) as jumlah')
                ->groupBy('status')
                ->pluck('jumlah', 'status')
                ->all();

            $tahunAktif = TahunAjaran::aktif()->first();

            if ($tahunAktif) {
                $rekapTahun = Absensi::where('siswa_id', $siswa->id)
                    ->whereHas('rombel', fn ($q) => $q->where('tahun_ajaran_id', $tahunAktif->id))
                    ->selectRaw('status, COUNT(DISTINCT tanggal) as jumlah')
                    ->groupBy('status')
                    ->pluck('jumlah', 'status')
                    ->all();
            }
        }

        return view('siswa.absensi', [
            'bulan' => $bulan,
            'riwayat' => $riwayat,
            'rekapBulan' => $rekapBulan,
            'rekapTahun' => $rekapTahun,
        ]);
    }
}
