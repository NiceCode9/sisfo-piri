<?php

namespace App\Http\Requests\Admin;

use App\Models\MataPelajaran;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePengampuBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Payload: guru[mapel_id] = guru_id|null. Baris kosong diabaikan.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'guru' => ['required', 'array'],
            'guru.*' => ['nullable', 'integer', 'exists:gurus,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $mapelIds = collect($this->input('guru', []))->keys()->filter(fn ($key) => is_numeric($key));

            if ($mapelIds->isEmpty()) {
                return;
            }

            $ada = MataPelajaran::whereIn('id', $mapelIds)->pluck('id')->all();
            $asing = $mapelIds->diff($ada);

            if ($asing->isNotEmpty()) {
                $validator->errors()->add('guru', 'Ada mapel yang tidak dikenal: '.$asing->implode(', ').'.');
            }
        });
    }
}
