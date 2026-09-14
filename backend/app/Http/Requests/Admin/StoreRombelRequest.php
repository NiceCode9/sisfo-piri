<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRombelRequest extends FormRequest
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
            'kelas_id' => [
                'required', 'integer', 'exists:kelas,id',
                Rule::unique('rombels', 'kelas_id')->where(
                    fn ($query) => $query->where('tahun_ajaran_id', $this->tahun_ajaran_id)
                ),
            ],
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
            'wali_guru_id' => ['nullable', 'integer', 'exists:gurus,id'],
        ];
    }
}
