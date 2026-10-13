<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\CalonSiswa;
use App\Support\RuteBeranda;
use Illuminate\View\View;

/**
 * Halaman PPDB untuk area siswa.
 *
 * Semula isi dashboard siswa, tapi dashboard sudah lama sama sekali bukan
 * ringkasan akademik: isinya status pendaftaran, berkas, dan pembayaran.
 * Sekarang keduanya terpisah supaya `siswa.dashboard` benar-benar dashboard.
 *
 * Pendaftar yang belum jadi siswa tetap punya akses ke sini lewat
 * {@see RuteBeranda}, jadi controller ini harus aman dipanggil
 * walau akun belum punya baris `siswas`.
 */
class PendaftaranController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        $calon = CalonSiswa::with([
            'berkasCalonSiswa',
            'sertifikatPrestasis',
            'jalurPendaftaran',
            'tahunAjaran',
            // `user` tidak pernah dipakai view, tapi ikut ter-eager-load dan
            // menambah satu query sia-sia.
            'logStatusPendaftaran',
            'pembayaran.biayaPendaftaran',
            'pembayaranLainnya',
        ])
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        $siswa = $user->siswa()->with('tahunAjaran')->first();
        $riwayat = CalonSiswa::with('tahunAjaran')->where('user_id', $user->id)->latest()->get();

        return view('siswa.pendaftaran', [
            'calon' => $calon,
            'siswa' => $siswa,
            'riwayat' => $riwayat,
        ]);
    }
}
