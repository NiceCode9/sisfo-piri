<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreJalurPendaftaranRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'nama_jalur' => ['required', 'string', 'max:100', 'unique:jalur_pendaftarans,nama_jalur'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'aktif' => ['required', 'boolean'],
            'wajib_sertifikat' => ['required', 'boolean'],
        ];
    }
}
