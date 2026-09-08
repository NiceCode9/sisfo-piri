<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCalonSiswaRequest extends FormRequest
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
            'tahun_ajaran_id' => ['nullable', 'integer', 'exists:tahun_ajarans,id'],
            'nik' => ['required', 'string', 'size:16', 'regex:/^[0-9]+$/', 'unique:calon_siswas,nik'],
            'nisn' => ['nullable', 'string', 'size:10', 'regex:/^[0-9]+$/', 'unique:calon_siswas,nisn'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'agama' => ['required', 'string', 'in:Islam,Kristen,Katolik,Hindu,Buddha,Khonghucu'],
            'alamat' => ['required', 'string', 'max:1000'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', 'unique:calon_siswas,email'],
            'asal_sekolah' => ['nullable', 'string', 'max:255'],
            'nama_ayah' => ['nullable', 'string', 'max:255'],
            'pekerjaan_ayah' => ['nullable', 'string', 'max:50'],
            'nama_ibu' => ['nullable', 'string', 'max:255'],
            'pekerjaan_ibu' => ['nullable', 'string', 'max:50'],
            'no_hp_orang_tua' => ['nullable', 'string', 'max:20'],
            'status_pendaftaran' => ['nullable', 'in:menunggu,diterima,ditolak,daftar_ulang'],
            'ijazah_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'kk_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'akta_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'foto_path' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'skl_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'sertifikat' => ['nullable', 'array', 'max:5'],
            'sertifikat.*.nama' => ['required_with:sertifikat', 'string', 'max:255'],
            'sertifikat.*.file' => ['required_with:sertifikat', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
