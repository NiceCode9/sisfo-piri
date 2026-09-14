<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGelombangRequest extends FormRequest
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
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
            'nama_gelombang' => ['required', 'string', 'max:100'],
            'nomor_urut' => ['required', 'integer', 'min:1'],
            'badge' => ['nullable', 'string', 'max:50'],
            'tanggal_buka' => ['required', 'date'],
            'tanggal_tutup' => ['required', 'date', 'after_or_equal:tanggal_buka'],
            'tanggal_tes' => ['nullable', 'date'],
            'tanggal_pengumuman' => ['nullable', 'date'],
            'kuota' => ['required', 'integer', 'min:1'],
            'terisi' => ['nullable', 'integer', 'min:0'],
            'diskon_persen' => ['nullable', 'integer', 'min:0', 'max:100'],
            'keuntungan' => ['nullable', 'array'],
            'keuntungan.*' => ['string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'warna_border' => ['nullable', 'string', 'max:50'],
            'is_aktif' => ['required', 'boolean'],
        ];
    }
}
