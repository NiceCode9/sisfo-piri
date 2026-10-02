<?php

namespace App\Rules;

use App\Models\TahunAjaran;
use App\Support\StatusPendaftaran;
use Closure;
use Illuminate\Contracts\Validation\ImplicitRule;

/**
 * Gelombang yang dipilih harus benar-benar bisa dipilih.
 *
 * Validasi `exists` saja tidak cukup: `exists` tetap lolos untuk gelombang
 * yang `is_aktif` sudah dimatikan, kuotanya penuh, atau tanggal buka/tutup-nya
 * sudah lewat.
 *
 * Mengimplementasikan `ImplicitRule` karena field-nya `nullable`: tanpa itu,
 * Laravel berhenti memvalidasi begitu nilainya null dan kewajiban memilih
 * gelombang tidak akan pernah dicek.
 *
 * Status ditentukan oleh `StatusPendaftaran` — objek yang sama dipakai badge di
 * landing page dan gate di form, supaya ketiganya tidak bisa berbeda pendapat.
 */
class GelombangTerbuka implements ImplicitRule
{
    /**
     * Dipakai oleh `DataAwareRule`/`Validator` yang masih memanggil bentuk lama.
     */
    public function passes($attribute, $value): bool
    {
        $gagal = false;

        $this->validate($attribute, $value, function () use (&$gagal): void {
            $gagal = true;
        });

        return ! $gagal;
    }

    public function message(): string
    {
        return 'Gelombang pendaftaran tidak tersedia.';
    }

    /**
     * @param  Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $status = StatusPendaftaran::tentukan(TahunAjaran::aktif()->first());

        if (! $status->dibuka()) {
            $fail($status->pesan);

            return;
        }

        // Sekolah yang belum mengatur gelombang sama sekali: pendaftaran tetap
        // jalan seperti sebelumnya, tanpa kewajiban memilih batch.
        if (! $status->wajibPilihGelombang()) {
            return;
        }

        if (! $value) {
            $fail('Pilih gelombang pendaftaran yang tersedia.');

            return;
        }

        if (! $status->tersedia->contains('id', (int) $value)) {
            $fail('Gelombang yang dipilih sudah penuh atau sudah lewat periodenya. Silakan pilih gelombang lain.');
        }
    }
}
