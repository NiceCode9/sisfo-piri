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
            // NULL = tidak dibatasi. Sama seperti Gelombang, `min:1` dipakai supaya `0`
            // tidak bisa dipakai sebagai penanda — dulu `0` di sini berarti
            // "selalu penuh" (`0 >= 0`), padahal di Gelombang berarti bebas.
            // Sekarang satu arti untuk keduanya: kosong.
            'kuota' => ['nullable', 'integer', 'min:1', 'gte:terisi'],
            'terisi' => ['nullable', 'integer', 'min:0'],
            // Kosong = pendaftaran tidak dibatasi jumlahnya. `lte:kuota` dipindah
            // ke withValidator() karena perbandingan dengan NULL selalu gagal.
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
            // Semua perbandingan `lte` dipindah ke sini. Sebelumnya `lte:kuota`
            // dipakai langsung di rules(), dan begitu `kuota` boleh NULL
            // (jalur tanpa batas penerimaan) perbandingan itu selalu gagal —
            // `5 <= null` tidak pernah benar.
            $kuota = $this->input('kuota');
            $terisi = $this->input('terisi');
            $kuotaPendaftaran = $this->input('kuota_pendaftaran');
            $terisiPendaftaran = (int) $this->input('terisi_pendaftaran', 0);

            // `terisi_pendaftaran` dihitung sistem, tapi form ini tetap
            // memvalidasi konsistensinya supaya angka tidak pernah lebih besar
            // dari kuota pendaftaran.
            if ($kuotaPendaftaran !== null && $kuotaPendaftaran !== '' && $terisiPendaftaran > (int) $kuotaPendaftaran) {
                $validator->errors()->add(
                    'kuota_pendaftaran',
                    'Kuota pendaftaran tidak boleh lebih kecil dari jumlah pendaftar saat ini.'
                );
            }

            // Tidak ada batas atas yang bisa diperiksa kalau penerimaan dan
            // pendaftaran keduanya tanpa batas.
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
