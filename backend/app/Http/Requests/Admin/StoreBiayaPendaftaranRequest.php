<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBiayaPendaftaranRequest extends FormRequest
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
            'jenis_biaya' => ['required', 'string', 'max:100'],
            'jumlah' => ['required', 'numeric', 'min:0'],
            'mata_uang' => ['nullable', 'string', 'max:10'],
            'wajib_bayar' => ['required', 'boolean'],
            'dapat_diangsur' => ['required', 'boolean'],
            'max_cicilan' => ['nullable', 'integer', 'min:1', 'max:60'],
            'min_dp' => ['nullable', 'numeric', 'min:0'],
            'jangka_waktu_hari' => ['nullable', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
