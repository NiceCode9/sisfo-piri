<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePembayaranRequest extends FormRequest
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
            'calon_siswa_id' => ['required', 'integer', 'exists:calon_siswas,id'],
            'biaya_pendaftaran_id' => ['nullable', 'integer', 'exists:biaya_pendaftarans,id'],
            'jumlah' => ['required', 'numeric', 'min:0'],
            'metode_pembayaran' => ['required', 'string', 'in:transfer,tunai'],
            'jenis_pembayaran' => ['nullable', 'string', 'in:penuh,dp_angsuran,cicilan_angsuran'],
            'bukti_pembayaran_path' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'tanggal_pembayaran' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:menunggu,berhasil,gagal'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'keterangan_angsuran' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
