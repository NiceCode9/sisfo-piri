<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePengampuRequest extends FormRequest
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
        return [
            'guru_id' => ['required', 'integer', 'exists:gurus,id'],
            'mata_pelajaran_id' => [
                'required', 'integer', 'exists:mata_pelajarans,id',
                Rule::unique('pengampus', 'mata_pelajaran_id')->where(
                    fn ($query) => $query->where('kelas_id', $this->kelas_id)->where('tahun_ajaran_id', $this->tahun_ajaran_id)
                ),
            ],
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
        ];
    }
}
