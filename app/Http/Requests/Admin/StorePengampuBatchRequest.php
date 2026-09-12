<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StorePengampuBatchRequest extends FormRequest
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
        $rombelId = $this->route('rombel')?->id;

        return [
            'baris' => ['required', 'array', 'min:1'],
            'baris.*.guru_id' => ['required', 'integer', 'exists:gurus,id'],
            'baris.*.mata_pelajaran_id' => [
                'required', 'integer', 'exists:mata_pelajarans,id',
                Rule::unique('pengampus', 'mata_pelajaran_id')->where(
                    fn ($query) => $query->where('rombel_id', $rombelId)
                ),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $mapels = collect($this->input('baris', []))->pluck('mata_pelajaran_id')->filter();

            if ($mapels->isNotEmpty() && $mapels->duplicates()->isNotEmpty()) {
                $validator->errors()->add('baris', 'Ada mapel yang dipilih lebih dari satu kali dalam batch ini.');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            back()->withErrors($validator)->withInput()->with('bukaModalPengampu', true)
        );
    }
}
