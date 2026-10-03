<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Services\RiwayatSiswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Riwayat terpadu seorang siswa, dilihat dari sisi guru.
 *
 * Ini melengkapi halaman detail siswa yang selama ini hanya menampilkan tiga
 * kolom riwayat kelas. Guru bisa membuka halaman ini untuk siswa yang ada di
 * rombel yang diampu — wali atau pengampu mapel — dan melihat seluruh
 * perjalanan akademik siswa itu lintas tahun.
 *
 * Otorisasi memakai {@see Rombel::terjangkauUser()}, sumber cakupan yang sama
 * dengan modul absensi, tugas, dan materi. Guru yang tidak mengampu siswa
 * tersebut mendapat 403, bukan halaman kosong — supaya tidak disalahpahami
 * sebagai "siswa ini tidak punya riwayat".
 */
class RiwayatSiswaController extends Controller
{
    public function show(Request $request, Siswa $siswa): View|RedirectResponse
    {
        if (! RiwayatSiswa::bolehAkses($request->user(), $siswa)) {
            abort(403, 'Siswa ini bukan anggota rombel yang Anda ampu.');
        }

        $baris = RiwayatSiswa::untukSiswa($siswa);

        return view('admin.siswas.riwayat', [
            'siswa' => $siswa->load(['user', 'kelas', 'tahunAjaran']),
            'baris' => $baris,
            'ringkas' => RiwayatSiswa::ringkas($baris),
        ]);
    }
}
