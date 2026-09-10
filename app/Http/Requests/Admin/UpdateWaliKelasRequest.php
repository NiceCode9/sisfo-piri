<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWaliKelasRequest extends FormRequest
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
        $id = $this->route('wali_kelas')->id;

        return [
            'guru_id' => ['required', 'integer', 'exists:gurus,id'],
            'kelas_id' => [
                'required', 'integer', 'exists:kelas,id',
                Rule::unique('wali_kelas', 'kelas_id')
                    ->ignore($id)
                    ->where(fn ($query) => $query->where('tahun_ajaran_id', $this->tahun_ajaran_id)),
            ],
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
        ];
    }
}
