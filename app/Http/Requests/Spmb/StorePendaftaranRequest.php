<?php

namespace App\Http\Requests\Spmb;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePendaftaranRequest extends FormRequest
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
            'jalur_pendaftaran_id' => ['required', 'integer', 'exists:jalur_pendaftarans,id'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'nik' => ['required', 'string', 'size:16', 'regex:/^[0-9]+$/', 'unique:calon_siswas,nik'],
            'nisn' => ['required', 'string', 'size:10', 'regex:/^[0-9]+$/', 'unique:calon_siswas,nisn'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'agama' => ['required', 'string', 'max:50'],
            'asal_sekolah' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string', 'max:1000'],
            'no_hp' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:calon_siswas,email'],
            'nama_ayah' => ['required', 'string', 'max:255'],
            'pekerjaan_ayah' => ['required', 'string', 'max:100'],
            'nama_ibu' => ['required', 'string', 'max:255'],
            'pekerjaan_ibu' => ['required', 'string', 'max:100'],
            'no_hp_orang_tua' => ['required', 'string', 'max:20'],
            'ijazah_path' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'kk_path' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'akta_path' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'foto_path' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'skl_path' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }
}
