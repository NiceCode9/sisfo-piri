<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBuktiAbsensiRequest;
use App\Http\Requests\Guru\StoreAbsensiIzinRequest;
use App\Models\Absensi;
use App\Models\Rombel;
use App\Services\PencatatanAbsensi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Penandaan izin/sakit milik kartu di halaman QR Absen (spek 2026-10-23).
 *
 * Pengecualian tulis sempit untuk role guru di sinilah tempatnya: endpoint
 * ini tidak menuntut `absensis.create`, tapi membawa penjaga sendiri —
 * rombel harus terjangkau, siswa harus anggota aktif rombel itu, status
 * hanya izin/sakit, dan baris hanya boleh baru. Koreksi lewat grid, bukan
 * kartu, sebagaimana aturan alasan koreksi yang ada.
 */
class AbsensiIzinController extends Controller
{
    public function store(StoreAbsensiIzinRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $rombel = Rombel::findOrFail((int) $data['rombel_id']);

        $this->evaluasiAkses($rombel, (int) $data['siswa_id']);

        $kunci = [
            'siswa_id' => (int) $data['siswa_id'],
            'rombel_id' => $rombel->id,
            'tanggal' => today()->toDateString(),
        ];
        $absensi = Absensi::firstOrNew($kunci);

        if ($absensi->exists) {
            return $this->keHalamanQr($rombel->id)
                ->with('error', 'Absensi siswa ini sudah tercatat — koreksinya lewat grid absensi.');
        }

        // Feld diisi eksplisit, bukan mass-assignment, agar penjaga di atas
        // tidak bisa dilewati lewat nama kolom yang diterima request.
        $absensi->status = $data['status'];
        $absensi->metode = 'manual';
        $absensi->dicatat_oleh = auth()->id();
        $absensi->rombel_id = $rombel->id;

        $tersimpan = PencatatanAbsensi::simpan($absensi, $kunci);

        if (! $tersimpan->is($absensi)) {
            // Balapan: baris terbukti sudah ada milik pencatatan lain.
            return $this->keHalamanQr($rombel->id)
                ->with('error', 'Absensi siswa ini sudah tercatat — koreksinya lewat grid absensi.');
        }

        $berkasPath = null;
        if ($request->hasFile('berkas')) {
            $berkasPath = $request->file('berkas')->store('absensi', 'berkas');
            $absensi->berkas_path = $berkasPath;
            $absensi->save();
        }

        $namaSiswa = $absensi->siswa->user?->name ?? 'Siswa';

        return $this->keHalamanQr($rombel->id)
            ->with('success', sprintf('%s ditandai %s.', $namaSiswa, $absensi->status));
    }

    public function storeBukti(StoreBuktiAbsensiRequest $request, Absensi $absensi): RedirectResponse
    {
        $this->evaluasiAkses($absensi->rombel, $absensi->siswa_id);

        abort_unless(
            in_array($absensi->status, ['sakit', 'izin'], true),
            422,
            'Bukti hanya bisa dilampirkan pada absensi berstatus sakit atau izin.'
        );

        // Satu catatan berbukti tunggal: penggantian membuang berkas lama.
        if ($absensi->berkas_path) {
            Storage::disk('berkas')->delete($absensi->berkas_path);
        }

        $absensi->berkas_path = $request->file('berkas')->store('absensi', 'berkas');
        $absensi->save();

        return redirect()->back()->with('success', 'Bukti berhasil diunggah.');
    }

    /** Penjaga sempit (spek: Model izin) yang jalur guru dan bukti-guru sama-sama panggil. */
    private function evaluasiAkses(Rombel $rombel, int $siswaId): void
    {
        abort_unless(
            Rombel::terjangkauOleh(
                request()->user(),
                $rombel->id,
                Rombel::ROLE_ABSENSI_UNIVERSAL
            ),
            403,
            'Rombel ini bukan cakupan Anda.'
        );

        abort_unless(in_array($siswaId, $rombel->anggotaIdsAktif(), true), 403, 'Siswa bukan anggota rombel ini.');
    }

    private function keHalamanQr(int $rombelId): RedirectResponse
    {
        return redirect()->route('guru.absensi-qr', ['rombel_id' => $rombelId]);
    }
}
