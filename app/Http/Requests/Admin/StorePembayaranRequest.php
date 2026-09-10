<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePembayaranRequest extends FormRequest
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
            'detail_angsuran_id' => ['nullable', 'integer', 'exists:detail_angsurans,id'],
            'jumlah' => ['required', 'numeric', 'min:0'],
            'metode_pembayaran' => ['required', 'string', 'in:transfer,tunai'],
            'jenis_pembayaran' => ['nullable', 'string', 'in:penuh,dp_angsuran,cicilan_angsuran'],
            'bukti_pembayaran_path' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'tanggal_pembayaran' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:menunggu,berhasil,gagal'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'keterangan_angsuran' => ['nullable', 'string', 'max:1000'],
            'buat_angsuran' => ['nullable', 'boolean'],
            'dp_dibayar' => ['required_if:buat_angsuran,1', 'nullable', 'numeric', 'min:0'],
            'jumlah_cicilan' => ['required_if:buat_angsuran,1', 'nullable', 'integer', 'min:1', 'max:60'],
            'tanggal_mulai' => ['required_if:buat_angsuran,1', 'nullable', 'date'],
            'redirect_to' => ['nullable', 'string', 'max:500'],
        ];
    }
}
