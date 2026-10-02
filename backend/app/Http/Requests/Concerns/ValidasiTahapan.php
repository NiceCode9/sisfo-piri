<?php

namespace App\Http\Requests\Concerns;

use App\Models\GelombangTahap;
use Illuminate\Validation\Validator;

trait ValidasiTahapan
{
    /**
     * Aturan untuk baris tahap (`tahapan`).
     *
     * Tahapan disimpan sebagai baris terpisah (tabel `gelombang_tahapan`), bukan
     * kolom tanggal di `gelombangs`, supaya tahap yang tidak diisi berarti tidak
     * ada baris dan tidak mungkin ada tahap yang separuh terisi.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function aturanTahapan(): array
    {
        return [
            // Minimal satu tahap. Tanpa baris apa pun, gelombang tidak punya
            // jadwal sama sekali.
            'tahapan' => ['required', 'array', 'min:1'],

            'tahapan.*.tipe' => ['required', 'string', 'in:'.implode(',', array_keys(GelombangTahap::TIPE))],
            'tahapan.*.nama_tahap' => ['required', 'string', 'max:100'],
            'tahapan.*.tanggal_mulai' => ['required', 'date'],

            // Tanggal selesai dikosongkan = tahap berlangsung satu hari.
            'tahapan.*.tanggal_selesai' => ['nullable', 'date'],
        ];
    }

    /**
     * Pesan error untuk aturan tahap.
     *
     * @return array<string, string>
     */
    protected function pesanTahapan(): array
    {
        return [
            'tahapan.required' => 'Tambahkan minimal satu tahap untuk gelombang ini.',
            'tahapan.min' => 'Tambahkan minimal satu tahap untuk gelombang ini.',
            'tahapan.*.tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tahapan.*.tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
        ];
    }

    /**
     * Checks yang tidak bisa ditulis sebagai aturan biasa.
     *
     * PENTING: method ini memakai `withValidator()`. Kalau class yang memakai
     * trait ini nanti menambah method `withValidator()` sendiri, method kelas
     * akan MENIMPA yang di trait dan seluruh pemeriksaan di bawah ikut hilang
     * tanpa error. Kalau butuh menambah validasi lain, taruh di trait ini.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $tahapan = $this->input('tahapan');

            if (! is_array($tahapan) || $tahapan === []) {
                return;
            }

            // Gelombang tanpa tahap pendaftaran tidak akan pernah bisa dipilih
            // pendaftar, karena itulah tahap yang jadi gate. Better gagal di
            // sini daripada diam-diam menjadi gelombang yang tidak bisa dipakai.
            if (! in_array('pendaftaran', array_column($tahapan, 'tipe'), true)) {
                $validator->errors()->add(
                    'tahapan',
                    'Gelombang wajib punya minimal satu tahap bertipe Pendaftaran.'
                );

                return;
            }

            // Tanggal selesai tidak boleh lebih awal dari tanggal mulai, dan
            // tahap harus berurutan kronologis sesuai urutan tampil.
            // Pemeriksaan per-baris memakai `after_or_equal:tahapan.*.tanggal_mulai`
            // tidak andal karena wildcard tidak selalu resolve, jadi keduanya
            // digabung di sini.
            $sebelumnya = null;

            foreach ($tahapan as $key => $tahap) {
                $mulai = $tahap['tanggal_mulai'] ?? null;
                $selesai = $tahap['tanggal_selesai'] ?? null;

                if ($mulai && $selesai && $selesai < $mulai) {
                    $validator->errors()->add(
                        "tahapan.{$key}.tanggal_selesai",
                        'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.'
                    );
                }

                if (! $mulai) {
                    continue;
                }

                if ($sebelumnya !== null && $mulai < $sebelumnya) {
                    $validator->errors()->add(
                        "tahapan.{$key}.tanggal_mulai",
                        'Tanggal mulai tahap ini tidak boleh lebih awal dari tahap sebelumnya.'
                    );
                }

                $sebelumnya = $mulai;
            }
        });
    }
}
