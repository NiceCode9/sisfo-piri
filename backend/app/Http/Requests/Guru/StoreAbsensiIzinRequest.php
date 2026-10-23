<?php

namespace App\Http\Requests\Guru;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Penandaan izin/sakit dari kartu QR guru (spek 2026-10-23).
 *
 * Aturan berkas sengaja sama persis dengan `StoreBuktiAbsensiRequest`
 * supaya tidak ada dua standar bukti dalam satu aplikasi. Yurisdiksi
 * rombel dan status baris dicek di controller (penjaga sempit), bukan
 * di sini.
 */
class StoreAbsensiIzinRequest extends FormRequest
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
            'rombel_id' => ['required', 'integer', 'exists:rombels,id'],
            'siswa_id' => ['required', 'integer', 'exists:siswas,id'],
            'status' => ['required', 'in:izin,sakit'],
            'berkas' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'berkas.image' => 'Berkas harus berupa gambar.',
            'berkas.mimes' => 'Format berkas harus JPG, JPEG, PNG, atau WEBP.',
            'berkas.max' => 'Ukuran berkas maksimal 10 MB.',
        ];
    }
}
