<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\BersihkanSertifikatKosong;
use App\Models\CalonSiswa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCalonSiswaRequest extends FormRequest
{
    use BersihkanSertifikatKosong;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->bersihkanSertifikat();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('calon_siswa')?->id ?? $this->route('calon_siswas')?->id;

        return [
            'jalur_pendaftaran_id' => ['required', 'integer', 'exists:jalur_pendaftarans,id'],
            'tahun_ajaran_id' => ['nullable', 'integer', 'exists:tahun_ajarans,id'],
            'nik' => ['required', 'string', 'size:16', 'regex:/^[0-9]+$/', 'unique:calon_siswas,nik,'.$id],
            'nisn' => ['nullable', 'string', 'size:10', 'regex:/^[0-9]+$/', 'unique:calon_siswas,nisn,'.$id],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'agama' => ['required', 'string', Rule::in(CalonSiswa::AGAMA)],
            'alamat' => ['required', 'string', 'max:1000'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', 'unique:calon_siswas,email,'.$id],
            'asal_sekolah' => ['nullable', 'string', 'max:255'],
            'nama_ayah' => ['nullable', 'string', 'max:255'],
            'pekerjaan_ayah' => ['nullable', 'string', 'max:50'],
            'nama_ibu' => ['nullable', 'string', 'max:255'],
            'pekerjaan_ibu' => ['nullable', 'string', 'max:50'],
            'no_hp_orang_tua' => ['nullable', 'string', 'max:20'],
            // Catatan: status_pendaftaran sengaja TIDAK bisa diubah lewat form edit.
            // Transisi status hanya lewat CalonSiswaController::updateStatus() agar
            // kuota, log status, akun siswa, dan tagihan selalu ikut terproses.
            'ijazah_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'kk_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'akta_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'foto_path' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'skl_path' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'krm_path' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'kip_path' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'sertifikat' => ['nullable', 'array', 'max:5'],
            'sertifikat.*.nama' => ['required_with:sertifikat', 'string', 'max:255'],
            'sertifikat.*.file' => ['required_with:sertifikat', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /**
     * Kandidat yang sudah punya sertifikat tidak wajib mengunggah ulang saat
     * admin mengoreksi data lain (mis. NIK yang salah ketik).
     */
    protected function sertifikatTersimpan(): bool
    {
        $calon = $this->route('calon_siswa') ?? $this->route('calonSiswa');

        if (! $calon) {
            return false;
        }

        return $calon->sertifikatPrestasis()->exists();
    }
}
