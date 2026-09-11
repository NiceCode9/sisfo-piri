<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSiswaRequest extends FormRequest
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
            'nis' => ['nullable', 'string', 'max:50', 'unique:siswas,nis'],
            'nisn' => ['required', 'string', 'size:10', 'regex:/^[0-9]+$/', 'unique:siswas,nisn', 'unique:users,username'],
            'nama' => ['required', 'string', 'max:255'],
            'tahun_ajaran_id' => ['nullable', 'integer', 'exists:tahun_ajarans,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'tanggal_diterima' => ['nullable', 'date'],
            'is_aktif' => ['required', 'boolean'],
            'nama_ayah' => ['nullable', 'string', 'max:255'],
            'pekerjaan_ayah' => ['nullable', 'string', 'max:50'],
            'nama_ibu' => ['nullable', 'string', 'max:255'],
            'pekerjaan_ibu' => ['nullable', 'string', 'max:50'],
            'no_hp_orang_tua' => ['nullable', 'string', 'max:20'],
        ];
    }
}
