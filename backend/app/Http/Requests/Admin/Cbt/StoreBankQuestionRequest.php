<?php

namespace App\Http\Requests\Admin\Cbt;

use App\Http\Requests\Concerns\ValidasiKunciJawaban;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBankQuestionRequest extends FormRequest
{
    use ValidasiKunciJawaban;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->aturanSoalDasar() + [
            'question_image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validasiKunciJawaban($validator);
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedData(): array
    {
        $data = parent::validated();

        if (is_array($data['correct_answer'] ?? null)) {
            $data['correct_answer'] = array_values(array_map('strval', $data['correct_answer']));
        }

        return $data;
    }
}
