<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKelasRequest extends FormRequest
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
        $id = $this->route('kelas')->id;

        return [
            'nama_kelas' => ['required', 'string', 'max:50', 'unique:kelas,nama_kelas,'.$id],
            'tingkat' => ['required', 'in:7,8,9'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
