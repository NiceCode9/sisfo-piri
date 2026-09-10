<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMataPelajaranRequest extends FormRequest
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
        $id = $this->route('mata_pelajaran')->id;

        return [
            'kode' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:mata_pelajarans,kode,'.$id],
            'nama' => ['required', 'string', 'max:255'],
            'kelompok' => ['required', 'in:A,B,C'],
            'kkm' => ['required', 'integer', 'min:0', 'max:100'],
            'is_aktif' => ['required', 'boolean'],
        ];
    }
}
