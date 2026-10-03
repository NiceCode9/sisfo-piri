<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\WaliMurid;
use App\Services\RiwayatSiswa;
use Illuminate\View\View;

/**
 * Riwayat terpadu anak asuh.
 *
 * Halaman `ortu.anak` sudah menampilkan rekap kehadiran per periode, tapi
 * belum nilai tugas dan nilai ujian. Halaman ini melengkapi keduanya dalam
 * satu tampilan per rombel.
 *
 * Kepemilikan dicek ulang di sini, bukan hanya lewat rute: parameter
 * `waliMurid` datang dari URL dan harus dipastikan milik pengguna yang
 * sedang login.
 */
class RiwayatController extends Controller
{
    public function show(WaliMurid $waliMurid): View
    {
        abort_unless($waliMurid->user_id === auth()->id(), 403, 'Bukan anak asuh Anda.');

        $siswa = $waliMurid->siswa;

        abort_if($siswa === null, 404);

        $baris = RiwayatSiswa::untukSiswa($siswa);

        return view('ortu.riwayat', [
            'waliMurid' => $waliMurid->load(['siswa.user', 'siswa.kelas', 'siswa.tahunAjaran']),
            'siswa' => $siswa,
            'baris' => $baris,
            'ringkas' => RiwayatSiswa::ringkas($baris),
        ]);
    }
}
