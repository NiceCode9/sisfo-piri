<?php

namespace App\Http\Requests\Siswa;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Filter halaman absensi siswa.
 *
 * `bulan` dipakai sebagai pola `LIKE`, jadi formatnya harus ketat. Tanpa
 * aturan ini pola wildcard-nya dikendalikan pengguna dan `?bulan=%` akan
 * mencocokkan seluruh tabel absensi.
 */
class AbsensiSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Regex-nya wajib, bukan cuma `date_format:Y-m`. `createFromFormat`
            // bersifat lenient dan menerima "2026" sebagai Y-m, sehingga bulan
            // yang hanya berisi tahun tetap lolos padahal bukan YYYY-MM.
            'bulan' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/', 'date_format:Y-m'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bulan.regex' => 'Bulan yang diminta tidak valid. Gunakan format YYYY-MM.',
            'bulan.date_format' => 'Bulan yang diminta tidak valid. Gunakan format YYYY-MM.',
        ];
    }
}
