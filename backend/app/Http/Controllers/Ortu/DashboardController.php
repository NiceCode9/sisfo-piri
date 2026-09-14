<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\WaliMurid;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Daftar anak + status kehadiran hari ini.
     */
    public function index(): View
    {
        $anak = WaliMurid::with(['siswa.user', 'siswa.kelas'])
            ->where('user_id', auth()->id())
            ->get();

        $hari = now()->toDateString();
        $statusHariIni = Absensi::whereIn('siswa_id', $anak->pluck('siswa_id'))
            ->where('tanggal', $hari)
            ->get()
            ->keyBy('siswa_id');

        return view('ortu.dashboard', [
            'anak' => $anak,
            'statusHariIni' => $statusHariIni,
            'hari' => $hari,
        ]);
    }

    /**
     * Detail satu anak: rekap bulanan + riwayat harian.
     */
    public function show(WaliMurid $waliMurid): View
    {
        abort_unless($waliMurid->user_id === auth()->id(), 403, 'Bukan anak asuh Anda.');

        $waliMurid->load(['siswa.user', 'siswa.kelas', 'siswa.tahunAjaran']);
        $bulan = request()->query('bulan', now()->format('Y-m'));

        $riwayat = Absensi::with('rombel.kelas')
            ->where('siswa_id', $waliMurid->siswa_id)
            ->where('tanggal', 'like', $bulan.'%')
            ->orderByDesc('tanggal')
            ->get();

        $rekap = Absensi::where('siswa_id', $waliMurid->siswa_id)
            ->where('tanggal', 'like', $bulan.'%')
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status')
            ->all();

        return view('ortu.anak', [
            'tautan' => $waliMurid,
            'bulan' => $bulan,
            'riwayat' => $riwayat,
            'rekap' => $rekap,
        ]);
    }
}
