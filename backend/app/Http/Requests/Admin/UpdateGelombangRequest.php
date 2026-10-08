<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ValidasiTahapan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGelombangRequest extends FormRequest
{
    use ValidasiTahapan;

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
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
            'nama_gelombang' => ['required', 'string', 'max:100'],
            'nomor_urut' => ['required', 'integer', 'min:1'],
            'badge' => ['nullable', 'string', 'max:50'],
            'kuota' => ['nullable', 'integer', 'min:1'],
            'terisi' => ['nullable', 'integer', 'min:0'],
            'diskon_persen' => ['nullable', 'integer', 'min:0', 'max:100'],
            'keuntungan' => ['nullable', 'array'],
            'keuntungan.*' => ['string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'warna_border' => ['nullable', 'string', 'max:50'],
            'is_aktif' => ['required', 'boolean'],
        ], $this->aturanTahapan());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->pesanTahapan();
    }
}
