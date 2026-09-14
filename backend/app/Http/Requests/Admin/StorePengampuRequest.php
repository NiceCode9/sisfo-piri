<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePengampuRequest extends FormRequest
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
            'guru_id' => ['required', 'integer', 'exists:gurus,id'],
            'mata_pelajaran_id' => [
                'required', 'integer', 'exists:mata_pelajarans,id',
                Rule::unique('pengampus', 'mata_pelajaran_id')->where(
                    fn ($query) => $query->where('rombel_id', $this->rombel_id)
                ),
            ],
            'rombel_id' => ['required', 'integer', 'exists:rombels,id'],
        ];
    }
}
