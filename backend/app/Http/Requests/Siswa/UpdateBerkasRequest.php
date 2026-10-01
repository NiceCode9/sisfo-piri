<?php

namespace App\Http\Requests\Siswa;

use App\Models\BerkasCalonSiswa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi upload ulang berkas oleh siswa.
 *
 * Cukup memvalidasi bentuk & ukuran file. Pertanyaan "field mana yang boleh
 * diunggah" adalah aturan bisnis dan ditangani di controller, karena hanya
 * controller yang tahu berkas mana yang sedang diminta diperbaiki.
 */
class UpdateBerkasRequest extends FormRequest
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
        $rules = [];

        foreach (BerkasCalonSiswa::UPLOADABLE as $field) {
            $rules[$field] = ['nullable', 'file', 'mimes:pdf', 'max:5120'];
        }

        // Pas foto tetap JPG/PNG dan 2MB, sama seperti form publik & admin.
        $rules['foto_path'] = ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'];

        return $rules;
    }
}
