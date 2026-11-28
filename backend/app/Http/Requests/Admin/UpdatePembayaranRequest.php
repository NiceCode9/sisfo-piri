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
            'jumlah' => ['required', 'numeric', 'min:0.01'],
            'metode_pembayaran' => ['required', 'string', 'in:transfer,tunai'],
            'bukti_pembayaran_path' => ['required_if:metode_pembayaran,transfer', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'tanggal_pembayaran' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'keterangan_angsuran' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
