<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePengaturanRequest extends FormRequest
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
            'batas_terlambat' => ['sometimes', 'required', 'date_format:H:i'],
            'jam_cek_belum_hadir' => ['sometimes', 'required', 'date_format:H:i'],
            'semester_ganjil_mulai' => ['sometimes', 'required', 'date_format:m-d'],
            'semester_ganjil_selesai' => ['sometimes', 'required', 'date_format:m-d'],
            'semester_genap_mulai' => ['sometimes', 'required', 'date_format:m-d'],
            'semester_genap_selesai' => ['sometimes', 'required', 'date_format:m-d'],
            'maintenance_mode' => ['sometimes', 'required', 'boolean'],
            'maintenance_pesan' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
