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
            'whatsapp_gateway_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'semester_aktif' => ['sometimes', 'required', 'in:ganjil,genap'],
            'batas_upload_mb' => ['sometimes', 'required', 'integer', 'min:1', 'max:50'],
            'maintenance_mode' => ['sometimes', 'required', 'boolean'],
            'maintenance_pesan' => ['sometimes', 'nullable', 'string', 'max:500'],
            'rekap_default_periode' => ['sometimes', 'required', 'in:minggu,bulan,ganjil,genap,tahun'],
            'notifikasi_ortu_aktif' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
