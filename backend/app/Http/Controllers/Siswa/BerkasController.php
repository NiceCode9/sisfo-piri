<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Siswa\UpdateBerkasRequest;
use App\Models\BerkasCalonSiswa;
use App\Models\CalonSiswa;
use App\Support\Berkas;
use Illuminate\Http\RedirectResponse;

class BerkasController extends Controller
{
    /**
     * Unggah ulang berkas yang diminta admin untuk diperbaiki.
     *
     * Sebelumnya siswa hanya bisa membaca dashboard: berkas berlabel "perlu
     * perbaikan" beserta alasannya tampil, tapi tidak ada satu pun route untuk
     * mengunggahenggantinya â€” dead end.
     *
     * Siswa hanya boleh mengisi field yang memang ditandai perlu perbaikan.
     * Field yang berhasil diunggah dikeluarkan dari daftar itu dan verifikasi
     * dikembalikan ke admin (reset ke belum terverifikasi).
     */
    public function update(UpdateBerkasRequest $request): RedirectResponse
    {
        $calon = CalonSiswa::where('user_id', $request->user()->id)->latest()->first();

        if (! $calon) {
            return back()->with('error', 'Data pendaftaran tidak ditemukan.');
        }

        $berkas = $calon->berkasCalonSiswa;

        if (! $berkas) {
            return back()->with('error', 'Belum ada data berkas pendaftaran.');
        }

        $diizinkan = $berkas->berkasPerluDiunggahUlang();

        if ($diizinkan === []) {
            return back()->with('error', 'Tidak ada berkas yang perlu diperbaiki saat ini.');
        }

        $terunggah = [];

        foreach ($diizinkan as $field) {
            if ($request->hasFile($field)) {
                $terunggah[$field] = $request->file($field);
            }
        }

        if ($terunggah === []) {
            return back()->with('error', 'Pilih minimal satu berkas untuk diunggah ulang.');
        }

        $files = new Berkas;

        foreach ($terunggah as $field => $file) {
            $pathBaru = $file->store('berkas', 'berkas');
            $files->ganti($pathBaru, $berkas->{$field});
            $berkas->{$field} = $pathBaru;
        }

        // Field yang sudah diganti tidak lagi ditandai perlu perbaikan, dan
        // verifikasi dikembalikan ke admin karena isinya sudah berubah.
        $sisa = array_values(array_diff($berkas->berkas_perlu_perbaikan ?? [], array_keys($terunggah)));

        $berkas->berkas_perlu_perbaikan = $sisa;
        $berkas->status_verifikasi = false;
        $berkas->catatan_berkas = trim(($berkas->catatan_berkas ?? '').PHP_EOL
            .'Siswa mengunggah ulang berkas pada '.now()->translatedFormat('d M Y H:i').'.');

        try {
            $berkas->save();
        } catch (\Throwable $e) {
            $files->buangYangBaru();

            throw $e;
        }

        $files->hapusYangSudahTidakDipakai();

        $nama = collect(array_keys($terunggah))
            ->map(fn (string $field): string => BerkasCalonSiswa::LABELS[$field] ?? $field)
            ->implode(', ');

        return back()->with(
            'success',
            "Berkas {$nama} berhasil diunggah ulang. Tunggu verifikasi admin.",
        );
    }
}
