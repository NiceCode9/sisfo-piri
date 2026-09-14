<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Admin\AbsensiController;
use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use App\Models\WaliMurid;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Daftar anak + status kehadiran hari ini + wali + persen bulan.
     */
    public function index(): View
    {
        $anak = WaliMurid::with(['siswa.user', 'siswa.kelas', 'siswa.tahunAjaran'])
            ->where('user_id', auth()->id())
            ->get();

        $hari = now()->toDateString();
        $statusHariIni = Absensi::whereIn('siswa_id', $anak->pluck('siswa_id'))
            ->where('tanggal', $hari)
            ->get()
            ->keyBy('siswa_id');

        $bulan = now()->format('Y-m');
        $waliPerSiswa = [];
        $persenPerSiswa = [];

        foreach ($anak as $tautan) {
            $siswa = $tautan->siswa;

            if ($siswa) {
                $rombel = Rombel::with('waliGuru')->where('kelas_id', $siswa->kelas_id)
                    ->where('tahun_ajaran_id', $siswa->tahun_ajaran_id)
                    ->first();
                $waliPerSiswa[$siswa->id] = $rombel?->waliGuru?->nama;

                $rekap = Absensi::where('siswa_id', $siswa->id)
                    ->where('tanggal', 'like', $bulan.'%')
                    ->selectRaw('status, COUNT(*) as jumlah')
                    ->groupBy('status')
                    ->pluck('jumlah', 'status')
                    ->all();
                $total = array_sum($rekap);
                $persenPerSiswa[$siswa->id] = $total ? round((($rekap['hadir'] ?? 0) + ($rekap['terlambat'] ?? 0)) / $total * 100) : null;
            }
        }

        return view('ortu.dashboard', [
            'anak' => $anak,
            'statusHariIni' => $statusHariIni,
            'waliPerSiswa' => $waliPerSiswa,
            'persenPerSiswa' => $persenPerSiswa,
            'hari' => $hari,
        ]);
    }

    /**
     * Detail satu anak: rekap periode (bulan/ganjil/genap/tahun) + riwayat.
     */
    public function show(WaliMurid $waliMurid): View
    {
        abort_unless($waliMurid->user_id === auth()->id(), 403, 'Bukan anak asuh Anda.');

        $waliMurid->load(['siswa.user', 'siswa.kelas', 'siswa.tahunAjaran']);

        $periode = request()->query('periode', 'bulan');
        $acuan = request()->query('acuan', now()->format('Y-m'));
        if ($periode === 'bulan' && strlen($acuan) === 10) {
            $acuan = substr($acuan, 0, 7);
        }
        $tahunAjaranId = request()->query('tahun_ajaran_id', $waliMurid->siswa->tahun_ajaran_id ?? TahunAjaran::aktif()->first()?->id);
        $acuanTanggal = $periode === 'bulan' ? $acuan.'-01' : (strlen($acuan) === 7 ? $acuan.'-01' : $acuan);
        $rentang = AbsensiController::rentangPeriode($periode, $acuanTanggal, $tahunAjaranId ? (int) $tahunAjaranId : null);

        $riwayat = Absensi::with('rombel.kelas')
            ->where('siswa_id', $waliMurid->siswa_id)
            ->whereBetween('tanggal', [$rentang['mulai'], $rentang['selesai']])
            ->orderByDesc('tanggal')
            ->get();

        $rekap = Absensi::where('siswa_id', $waliMurid->siswa_id)
            ->whereBetween('tanggal', [$rentang['mulai'], $rentang['selesai']])
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status')
            ->all();

        $total = array_sum($rekap);
        $persen = $total ? round((($rekap['hadir'] ?? 0) + ($rekap['terlambat'] ?? 0)) / $total * 100) : null;

        $rombel = $waliMurid->siswa ? Rombel::with('waliGuru')->where('kelas_id', $waliMurid->siswa->kelas_id)
            ->where('tahun_ajaran_id', $waliMurid->siswa->tahun_ajaran_id)->first() : null;

        return view('ortu.anak', [
            'tautan' => $waliMurid,
            'periode' => $periode,
            'acuan' => $acuan,
            'rentang' => $rentang,
            'riwayat' => $riwayat,
            'rekap' => $rekap,
            'persen' => $persen,
            'wali' => $rombel?->waliGuru?->nama,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAjaranId' => $tahunAjaranId,
        ]);
    }
}
