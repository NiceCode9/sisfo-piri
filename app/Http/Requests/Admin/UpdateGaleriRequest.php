<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGaleriRequest extends FormRequest
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
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'tanggal' => ['required_if:tipe,prestasi', 'nullable', 'date'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Gambar wajib bila tipe galeri dan baris belum punya gambar.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $galeri = $this->route('galeri');

            if ($this->input('tipe') === 'galeri' && ! $this->hasFile('image') && ! $galeri?->image_path) {
                $validator->errors()->add('image', 'Gambar wajib untuk tipe galeri.');
            }
        });
    }
}
