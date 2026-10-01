<?php

namespace App\Http\Requests\Admin;

use App\Models\Guru;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEkstrakurikulerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Gabungkan pilihan pembina menjadi satu kolom `pembina`:
     * - `pembina_pilih = __luar__` → pakai `pembina_manual` (pembina luar sekolah).
     * - selain itu → pakai nama guru yang dipilih.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['pembina' => $this->resolvePembina()]);
    }

    private function resolvePembina(): ?string
    {
        $pilih = trim((string) $this->input('pembina_pilih', ''));

        if ($pilih === '__luar__') {
            $manual = trim((string) $this->input('pembina_manual', ''));

            return $manual === '' ? null : $manual;
        }

        if ($pilih === '') {
            return null;
        }

        return Guru::where('nama', $pilih)->aktif()->exists() ? $pilih : null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('ekstrakurikuler')->id;

        return [
            'kode' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:ekstrakurikulers,kode,'.$id],
            'nama' => ['required', 'string', 'max:255'],
            'pembina_pilih' => ['nullable', 'string', 'max:255'],
            'pembina_manual' => ['nullable', 'string', 'max:255', 'required_if:pembina_pilih,__luar__'],
            'pembina' => ['nullable', 'string', 'max:255'],
            'jadwal' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'is_aktif' => ['required', 'boolean'],
        ];
    }
}
