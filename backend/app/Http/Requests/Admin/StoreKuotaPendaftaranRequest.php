<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKuotaPendaftaranRequest extends FormRequest
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
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
            'jalur_pendaftaran_id' => [
                'required', 'integer', 'exists:jalur_pendaftarans,id',
                Rule::unique('kuota_pendaftarans', 'jalur_pendaftaran_id')->where(
                    fn ($query) => $query->where('tahun_ajaran_id', $this->tahun_ajaran_id)
                ),
            ],
            'kuota' => ['required', 'integer', 'min:0'],
            'terisi' => ['nullable', 'integer', 'min:0', 'lte:kuota'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
