<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\CalonSiswa;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $calon = CalonSiswa::with(['berkasCalonSiswa', 'jalurPendaftaran', 'tahunAjaran', 'logStatusPendaftaran.user'])
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        $siswa = $user->siswa()->with('tahunAjaran')->first();
        $riwayat = CalonSiswa::with('tahunAjaran')->where('user_id', $user->id)->latest()->get();

        return view('siswa.dashboard', [
            'calon' => $calon,
            'siswa' => $siswa,
            'riwayat' => $riwayat,
        ]);
    }
}
