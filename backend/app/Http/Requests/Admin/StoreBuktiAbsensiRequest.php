<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Unggah bukti pendukung untuk absensi berstatus sakit atau izin.
 *
 * Bukti adalah foto surat dokter atau surat izin. Aturan jenis dan ukuran
 * sengaja sama dengan unggah berkas lain di aplikasi ini (`image`,
 * `mimes:jpg,jpeg,png,webp`, 10 MB) supaya tidak ada dua standar berbeda.
 */
class StoreBuktiAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi rombel dan keabsenan ditangani di controller; middleware
        // `absensis.create` sudah menjawab "boleh mencatat".
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'berkas' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'berkas.required' => 'Pilih foto surat dokter atau surat izin.',
            'berkas.image' => 'Berkas harus berupa gambar.',
            'berkas.mimes' => 'Format berkas harus JPG, JPEG, PNG, atau WEBP.',
            'berkas.max' => 'Ukuran berkas maksimal 10 MB.',
        ];
    }
}
