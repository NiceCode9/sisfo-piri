<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKuotaPendaftaranRequest extends FormRequest
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
        $id = $this->route('kuota_pendaftaran')->id;

        return [
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
            'jalur_pendaftaran_id' => [
                'required', 'integer', 'exists:jalur_pendaftarans,id',
                Rule::unique('kuota_pendaftarans', 'jalur_pendaftaran_id')
                    ->ignore($id)
                    ->where(fn ($query) => $query->where('tahun_ajaran_id', $this->tahun_ajaran_id)),
            ],
            'kuota' => ['required', 'integer', 'min:0', 'gte:terisi'],
            'terisi' => ['nullable', 'integer', 'min:0', 'lte:kuota'],
            // Kosong = pendaftaran tidak dibatasi jumlahnya.
            'kuota_pendaftaran' => ['nullable', 'integer', 'min:0'],
            'terisi_pendaftaran' => ['nullable', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'kuota_pendaftaran.lte' => 'Kuota pendaftaran tidak boleh lebih kecil dari jumlah pendaftar saat ini.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $terisi = (int) $this->input('terisi_pendaftaran', 0);
            $kuota = $this->input('kuota_pendaftaran');

            if ($kuota !== null && $kuota !== '' && $terisi > (int) $kuota) {
                $validator->errors()->add(
                    'kuota_pendaftaran',
                    'Kuota pendaftaran tidak boleh lebih kecil dari jumlah pendaftar saat ini.'
                );
            }
        });
    }
}
