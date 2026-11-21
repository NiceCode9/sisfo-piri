<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class StoreAbsensiManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'siswa_id' => ['required', 'integer', 'exists:siswas,id'],
            'status' => ['required', 'in:hadir,terlambat,izin,sakit,alpa'],
            'mode' => ['nullable', 'in:antrian,semua'],
        ];
    }
}
