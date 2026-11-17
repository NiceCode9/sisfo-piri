<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFasilitasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis' => ['required', 'string', 'max:100'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'aktif' => ['nullable', 'boolean'],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'hapus_foto' => ['nullable', 'array'],
            'hapus_foto.*' => ['string'],
        ];
    }
}
