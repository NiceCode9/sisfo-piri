<?php

namespace App\Rules;

use App\Models\Gelombang;
use App\Models\JadwalPpdb;
use App\Models\TahunAjaran;
use Closure;
use Illuminate\Contracts\Validation\ImplicitRule;

/**
 * Gelombang yang dipilih harus benar-benar bisa dipilih.
 *
 * Validasi `exists` saja tidak cukup: `exists` tetap lolos untuk gelombang
 * yang `is_aktif` sudah dimatikan, kuotanya penuh, atau tanggal buka/tutup-nya
 * sudah lewat. Aturan ini juga menegakkan kewajiban memilih gelombang selama
 * masih ada gelombang yang terbuka.
 *
 * Mengimplementasikan `ImplicitRule` karena field-nya `nullable`: tanpa itu,
 * Laravel berhenti memvalidasi begitu nilainya null dan kewajiban memilih
 * gelombang tidak akan pernah dicek.
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
        $tahun = TahunAjaran::aktif()->first();

        if (! $tahun) {
            return;
        }

        // Gelombang hanya relevan saat pendaftaran benar-benar dibuka. Kalau
        // jadwal sudah ditutup, biarkan controller memberi pesan
        // "pendaftaran sudah ditutup" daripada membingungkan pendaftar dengan
        // error gelombang.
        $jendela = JadwalPpdb::jendelaPendaftaran($tahun);

        if ($jendela && ! $jendela->sedangBerlangsung()) {
            return;
        }

        $tersedia = Gelombang::terbuka($tahun);

        // Sekolah yang belum memakai gelombang sama sekali tetap bisa
        // menerima pendaftaran seperti sebelumnya.
        if ($tersedia->isEmpty()) {
            return;
        }

        if (! $value) {
            $fail('Pilih gelombang pendaftaran yang tersedia.');

            return;
        }

        if (! $tersedia->firstWhere('id', (int) $value)) {
            $fail('Gelombang yang dipilih sudah penuh atau sudah lewat periodenya. Silakan pilih gelombang lain.');
        }
    }
}
