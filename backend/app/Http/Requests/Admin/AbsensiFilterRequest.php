<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ValidasiPeriodeAbsensi;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Filter layar absensi: grid harian, rekap, dan ekspor.
 *
 * Filter ini datang dari query string dan dirender ulang ke form, jadi nilainya
 * tidak pernah dipercaya. Tanpa validasi, `?periode=ngawur` diam-diam
 * menampilkan rekap satu tahun penuh, dan `?tanggal=bukan-tanggal` membuat
 * halaman gagal dengan 500.
 */
class AbsensiFilterRequest extends FormRequest
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
            // Jangkauan rombel tetap dicek di controller, bukan di sini: yang
            // boleh tampil bukan hanya soal format, tapi soal penugasan user.
            'rombel_id' => ['nullable', 'integer', 'exists:rombels,id'],
            'tahun_ajaran_id' => ['nullable', 'integer', 'exists:tahun_ajarans,id'],
            'tanggal' => ['nullable', 'date', 'before_or_equal:today'],
        ], $this->aturanPeriodeAbsensi());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge([
            'tanggal.date' => 'Tanggal pencatatan tidak valid.',
            'tanggal.before_or_equal' => 'Tanggal pencatatan tidak boleh melewati hari ini.',
        ], $this->pesanPeriodeAbsensi());
    }
}
