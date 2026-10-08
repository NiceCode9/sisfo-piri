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
            // NULL = tidak dibatasi. `min:1` supaya `0` tidak dipakai sebagai penanda;
            // dulu `0` berarti "selalu penuh" di sini dan "bebas" di Gelombang.
            'kuota' => ['nullable', 'integer', 'min:1', 'gte:terisi'],
            'terisi' => ['nullable', 'integer', 'min:0'],
            // Kosong = pendaftaran tidak dibatasi jumlahnya. `lte:kuota` dan
            // `lte:kuota_pendaftaran` dipindah ke withValidator() di bawah.
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
            // `lte` tidak bisa dipakai di rules() begitu `kuota` boleh NULL:
            // `5 <= null` selalu salah, jadi jalur tanpa batas penerimaan tidak
            // akan bisa disimpan sama sekali.
            $kuota = $this->input('kuota');
            $terisi = $this->input('terisi');
            $kuotaPendaftaran = $this->input('kuota_pendaftaran');
            $terisiPendaftaran = (int) $this->input('terisi_pendaftaran', 0);

            if ($kuotaPendaftaran !== null && $kuotaPendaftaran !== '' && $terisiPendaftaran > (int) $kuotaPendaftaran) {
                $validator->errors()->add(
                    'kuota_pendaftaran',
                    'Kuota pendaftaran tidak boleh lebih kecil dari jumlah pendaftar saat ini.'
                );
            }

            if ($kuota === null || $kuota === '') {
                return;
            }

            if ($terisi !== null && $terisi !== '' && (int) $terisi > (int) $kuota) {
                $validator->errors()->add(
                    'terisi',
                    'Jumlah diterima tidak boleh lebih besar dari kuota penerimaan.'
                );
            }

            if ($kuotaPendaftaran !== null && $kuotaPendaftaran !== '' && (int) $kuotaPendaftaran > (int) $kuota) {
                $validator->errors()->add(
                    'kuota_pendaftaran',
                    'Kuota pendaftaran tidak boleh lebih besar dari kuota penerimaan untuk jalur ini.'
                );
            }
        });
    }
}
