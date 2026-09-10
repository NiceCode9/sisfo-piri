<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreGaleriRequest extends FormRequest
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
            'tipe' => ['required', 'in:galeri,prestasi'],
            'title' => ['required', 'string', 'max:255'],
            'desc' => ['nullable', 'string', 'max:500'],
            'image' => ['required_if:tipe,galeri', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'tanggal' => ['required_if:tipe,prestasi', 'nullable', 'date'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
