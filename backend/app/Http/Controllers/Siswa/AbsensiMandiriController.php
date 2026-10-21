<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Pengaturan;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PencatatanAbsensi;
use App\Services\TokenQrAbsensi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Scan QR absen mandiri dari HP siswa.
 *
 * Alur arah baru (keputusan client): siswa memindai QR yang ditampilkan guru
 * di kelas jam pertama. Server yang memutuskan jendela dan status, jam HP
 * tidak dipercaya, dan guru pemilik QR ikut tercatat di baris absensi.
 */
class AbsensiMandiriController extends Controller
{
    public function show(): View
    {
        $siswa = $this->siswaLogin();

        return view('siswa.absensi.scan', [
            'jam_buka' => Pengaturan::nilai('jam_buka_absen', '07:00'),
            'batas_terlambat' => Pengaturan::nilai('batas_terlambat', '07:15'),
            'jam_tutup' => Pengaturan::nilai('jam_tutup_absen', '07:45'),
            'nama' => $siswa?->user?->name ?? '-', 'kelas' => $this->rombelSiswa($siswa)?->kelas?->nama_kelas,
            'tercatat' => $this->barisHariIni($siswa),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:500'],
        ]);

        $kunciQr = TokenQrAbsensi::baca($data['token']);

        if (! $kunciQr) {
            return response()->json(['message' => 'QR tidak berlaku. Minta guru menampilkan QR terbaru, lalu scan lagi.'], 422);
        }

        $guruUser = User::find($kunciQr['user_id']);

        if (! $guruUser) {
            return response()->json(['message' => 'QR tidak berlaku.'], 422);
        }

        $siswa = $this->siswaLogin();

        if (! $siswa) {
            return response()->json(['message' => 'Akun ini belum terhubung data siswa. Hubungi admin.'], 422);
        }

        $rombel = $this->rombelSiswa($siswa);

        if (! $rombel) {
            return response()->json(['message' => 'Kelas kamu belum terdaftar. Hubungi admin.'], 422);
        }

        // Catatan manual guru (sakit, izin, alpa, atau hadir manual) tidak
        // pernah ditimpa scan: perubahan lewat jalur guru yang wajib beralasan
        // dan meninggalkan riwayat koreksi.
        $tercatatManual = $this->barisHariIni($siswa);

        if ($tercatatManual && $tercatatManual->metode === 'manual') {
            return response()->json(['message' => 'Absensi kamu hari ini sudah dicatat guru: '.$tercatatManual->status.'.'], 422);
        }

        $buka = Pengaturan::nilai('jam_buka_absen', '07:00');
        $tutup = Pengaturan::nilai('jam_tutup_absen', '07:45');
        $batas = Pengaturan::nilai('batas_terlambat', '07:15');
        $sekarang = now();
        $jam = $sekarang->format('H:i');

        if ($jam < $buka) {
            return response()->json(['message' => 'Absen belum dibuka. Scan mulai jam '.$buka.'.'], 422);
        }

        if ($jam >= $tutup) {
            return response()->json(['message' => 'Absen sudah ditutup jam '.$tutup.'. Minta guru mencatat manual.'], 422);
        }

        $status = $jam >= $batas ? 'terlambat' : 'hadir';

        $absensi = PencatatanAbsensi::catat($siswa->id, $rombel->id, $sekarang, $status, 'qr', $guruUser->id);

        if ($absensi->metode === 'manual') {
            // Guru memenangkan balapan persis di celah cek di atas: barisnya
            // tetap milik guru dan pengirim scan diberi tahu.
            return response()->json(['message' => 'Absensi kamu hari ini sudah dicatat guru: '.$absensi->status.'.'], 422);
        }

        return response()->json([
            'message' => $absensi->status === 'hadir'
                ? 'Tercatat hadir jam '.substr((string) $absensi->jam_datang, 0, 5).'.'
                : 'Tercatat terlambat jam '.substr((string) $absensi->jam_datang, 0, 5).'.',
            'status' => $absensi->status,
            'jam' => $absensi->jam_datang,
            'guru' => $guruUser->name,
            'baru' => $absensi->wasRecentlyCreated,
        ]);
    }

    protected function siswaLogin(): ?Siswa
    {
        return auth()->user()?->siswa()->first();
    }

    /**
     * Rombel berjalan siswa, dari riwayat kelas berstatus aktif.
     *
     * Sama pendekannya dengan sumber keanggotaan grid manual
     * (`Rombel::anggotaIdsAktif()`), jadi scan selalu jatuh ke rombel yang
     * sama dengan yang dipakai guru mengisi grid.
     */
    protected function rombelSiswa(?Siswa $siswa): ?Rombel
    {
        if (! $siswa) {
            return null;
        }

        $riwayat = $siswa->riwayatKelas()
            ->where('status', 'aktif')
            ->orderByDesc('id')
            ->first();

        if (! $riwayat) {
            return null;
        }

        return Rombel::with('kelas')
            ->where('kelas_id', $riwayat->kelas_id)
            ->where('tahun_ajaran_id', $riwayat->tahun_ajaran_id)
            ->orderBy('id')
            ->first();
    }

    protected function barisHariIni(?Siswa $siswa): ?Absensi
    {
        if (! $siswa) {
            return null;
        }

        return Absensi::where('siswa_id', $siswa->id)
            ->whereDate('tanggal', now()->toDateString())
            ->first();
    }
}
