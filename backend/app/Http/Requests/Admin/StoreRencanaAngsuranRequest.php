<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRencanaAngsuranRequest extends FormRequest
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
            'dp_dibayar' => ['required', 'numeric', 'min:0'],
            'jumlah_cicilan' => ['required', 'integer', 'min:1'],
            'tanggal_mulai' => ['required', 'date'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
