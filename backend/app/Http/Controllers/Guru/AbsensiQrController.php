<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use App\Services\TokenQrAbsensi;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * QR absen harian milik akun guru yang menampilkan QR di kelas jam pertama.
 *
 * QR-nya digenerate server tanpa tulis ke DB (look {@see TokenQrAbsensi()}) dan
 * berubah sendiri tiap interval `menit_ganti_qr`; halaman menata ulang QR itu
 * lewat endpoint refresh.
 */
class AbsensiQrController extends Controller
{
    /** Info jendela jam yang ditemukan di layar guru, dari satu sumber pengaturan. */
    private function jendela(): array
    {
        return [
            'jam_buka' => Pengaturan::nilai('jam_buka_absen', '07:00'),
            'batas_terlambat' => Pengaturan::nilai('batas_terlambat', '07:15'),
            'jam_tutup' => Pengaturan::nilai('jam_tutup_absen', '07:45'),
        ];
    }

    public function show(): View
    {
        $token = TokenQrAbsensi::generate((int) auth()->id());

        return view('guru.absensi-qr', $this->jendela() + [
            'qrSvg' => (string) QrCode::size(320)->generate($token),
            'menit_ganti' => TokenQrAbsensi::menitGanti(),
            'refreshRoute' => route('guru.absensi-qr.refresh'),
        ]);
    }

    public function refresh(): JsonResponse
    {
        $token = TokenQrAbsensi::generate((int) auth()->id());
        $menit = TokenQrAbsensi::menitGanti();
        $sekarang = now();
        $slot = TokenQrAbsensi::slot($sekarang);

        return response()->json([
            'qr' => (string) QrCode::size(320)->generate($token),
            'interval_menit' => $menit,
            'ganti_pada' => $sekarang->copy()->startOfDay()->addMinutes(($slot + 1) * $menit)->format('H:i'),
        ]);
    }
}
