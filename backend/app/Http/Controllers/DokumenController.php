<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\BerkasCalonSiswa;
use App\Models\CalonSiswa;
use App\Models\Pembayaran;
use App\Models\PembayaranLainnya;
use App\Models\Rombel;
use App\Models\SertifikatPrestasi;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyaji dokumen privat: berkas PPDB, sertifikat, dan bukti pembayaran.
 *
 * Dokumen ini tidak disimpan di disk `public`, sehingga satu-satunya cara
 * membacanya adalah lewat route di kelas ini. Setiap route memverifikasi
 * pemilik dokumen sebelum mengalirkan file ke browser.
 */
class DokumenController extends Controller
{
    public function berkas(BerkasCalonSiswa $berkas, string $field): StreamedResponse
    {
        abort_unless(in_array($field, BerkasCalonSiswa::UPLOADABLE, true), 404);

        $path = $berkas->{$field};
        $calon = $berkas->calonSiswa;

        abort_if(! $calon || ! $path, 404);

        $this->authorizePemilik($calon, 'berkas-calon-siswas.view');

        return $this->alirkan($path);
    }

    public function sertifikat(SertifikatPrestasi $sertifikat): StreamedResponse
    {
        $calon = $sertifikat->calonSiswa;

        abort_if(! $calon || ! $sertifikat->file_path, 404);

        $this->authorizePemilik($calon, 'berkas-calon-siswas.view');

        return $this->alirkan($sertifikat->file_path);
    }

    public function pembayaran(Pembayaran $pembayaran): StreamedResponse
    {
        abort_if(! $pembayaran->bukti_pembayaran_path, 404);

        $this->authorizePemilik($pembayaran->calonSiswa, 'pembayarans.view');

        return $this->alirkan($pembayaran->bukti_pembayaran_path);
    }

    public function pembayaranLainnya(PembayaranLainnya $pembayaranLainnya): StreamedResponse
    {
        abort_if(! $pembayaranLainnya->bukti_pembayaran_path, 404);

        $this->authorizePemilik($pembayaranLainnya->calonSiswa, 'pembayaran-lainnyas.view');

        return $this->alirkan($pembayaranLainnya->bukti_pembayaran_path);
    }

    /**
     * Bukti sakit/izin pada satu baris absensi.
     *
     * Dibaca oleh user yang jangkauan rombelnya, oleh siswa itu sendiri, atau
     * oleh orang tuanya. Bukti ini berisi informasi kesehatan, jadi tidak
     * boleh terbuka hanya karena punya permission melihat rekap absensi.
     */
    public function absensi(Absensi $absensi): StreamedResponse
    {
        abort_if(! $absensi->berkas_path, 404);

        /** @var User $user */
        $user = auth()->user();
        abort_if(! $user, 401);

        if (Rombel::terjangkauOleh($user, $absensi->rombel_id, Rombel::ROLE_ABSENSI_UNIVERSAL)) {
            return $this->alirkan($absensi->berkas_path);
        }

        $siswa = $absensi->siswa;
        $isSiswa = $siswa !== null && $siswa->user_id === $user->id;
        $isWali = $siswa !== null
            && $siswa->waliMurids()->where('user_id', $user->id)->exists();

        abort_unless($isSiswa || $isWali, 403);

        return $this->alirkan($absensi->berkas_path);
    }

    /**
     * Dokumen boleh dibaca oleh admin dengan permission terkait, calon pemilik
     * berkas, siswa hasil penerimaan, atau wali dari siswa tersebut.
     */
    private function authorizePemilik(CalonSiswa $calon, string $permission): void
    {
        /** @var User $user */
        $user = auth()->user();
        abort_if(! $user, 401);

        if ($user->can($permission)) {
            return;
        }

        $calon->loadMissing('siswa.waliMurids');
        $siswa = $calon->siswa;

        $isCalonSendiri = $calon->user_id === $user->id;
        $isSiswa = $siswa !== null && $siswa->user_id === $user->id;
        $isWali = $siswa !== null && $siswa->waliMurids->contains('user_id', $user->id);

        abort_unless($isCalonSendiri || $isSiswa || $isWali, 403);
    }

    private function alirkan(string $path): StreamedResponse
    {
        abort_unless(Storage::disk('berkas')->exists($path), 404);

        return Storage::disk('berkas')->response($path);
    }
}
