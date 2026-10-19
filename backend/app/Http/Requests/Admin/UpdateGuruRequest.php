<?php

namespace App\Http\Requests\Admin;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGuruRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, Closure|array<mixed>|string>
     */
    public function rules(): array
    {
        $guru = $this->route('guru');

        return [
            'nip' => ['nullable', 'string', 'max:50', 'unique:gurus,nip,'.$guru->id],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'telp' => ['required', 'string', 'max:50', 'unique:users,username,'.$guru->user_id, $this->harusAngka()],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'is_aktif' => ['required', 'boolean'],
        ];
    }

    private function harusAngka(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! preg_match('/^[0-9]+$/', (string) $value)) {
                $fail('No. HP harus angka saja, tanpa spasi atau tanda tambah.');
            }
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'telp.unique' => 'No. HP sudah dipakai username akun lain.',
        ];
    }
}
