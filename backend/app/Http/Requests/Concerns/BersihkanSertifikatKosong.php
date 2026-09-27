<?php

namespace App\Http\Requests\Concerns;

use App\Models\JalurPendaftaran;

trait BersihkanSertifikatKosong
{
    /**
     * Buang baris sertifikat yang benar-benar kosong lalu hapus key-nya bila
     * tidak ada isi sama sekali.
     *
     * Browser tetap mengirim `<input name="sertifikat[0][nama]">` yang kosong
     * walau seksi sertifikat disembunyikan (jalur tidak mewajibkan sertifikat).
     * Akibatnya `required_with:sertifikat` Always gagal walau jalur reguler.
     * Membersihkan di sini membuat aturan validasi hanya berlaku atas baris
     * yang memang diisi pendaftar.
     */
    protected function bersihkanSertifikat(): void
    {
        $baris = $this->input('sertifikat');

        if (! is_array($baris) || $baris === []) {
            $this->request->remove('sertifikat');

            return;
        }

        $berisi = [];

        foreach ($baris as $index => $barisatu) {
            $nama = is_array($barisatu) ? trim((string) ($barisatu['nama'] ?? '')) : '';

            if ($nama === '' && ! $this->sertifikatPunyaBerkas($index)) {
                continue;
            }

            $berisi[$index] = $barisatu;
        }

        if ($berisi === []) {
            $this->request->remove('sertifikat');

            return;
        }

        $this->merge(['sertifikat' => $berisi]);
    }

    /**
     * Cek keberadaan file pada indeks tertentu, dengan fallback ke array file
     * karena `hasFile()` bisa kosong bila input kosong tidak terkirim sama sekali.
     */
    private function sertifikatPunyaBerkas(int|string $index): bool
    {
        if ($this->hasFile("sertifikat.{$index}.file")) {
            return true;
        }

        $files = $this->file('sertifikat');

        return is_array($files) && ! empty($files[$index]['file']);
    }

    /**
     * Apakah jalur yang dipilih mewajibkan sertifikat prestasi?
     */
    protected function jalurWajibSertifikat(): bool
    {
        return (bool) JalurPendaftaran::find($this->input('jalur_pendaftaran_id'))?->wajib_sertifikat;
    }
}
