<?php

namespace App\Http\Requests\Ortu;

use App\Http\Requests\Concerns\ValidasiPeriodeAbsensi;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Filter rekap kehadiran satu anak di halaman orang tua.
 *
 * Nilainya diteruskan ke `AbsensiController::rentangPeriode()`, jadi aturan
 * periodenya harus sama dengan yang dipakai admin. Tanpa itu, `?periode=ngawur`
 * diam-diam berubah menjadi rekap satu tahun penuh.
 */
class AnakAbsensiRequest extends FormRequest
{
    use ValidasiPeriodeAbsensi;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge([
            'tahun_ajaran_id' => ['nullable', 'integer', 'exists:tahun_ajarans,id'],
        ], $this->aturanPeriodeAbsensi());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->pesanPeriodeAbsensi();
    }
}
