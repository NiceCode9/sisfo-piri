<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMateriRequest extends FormRequest
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
            'rombel_id' => ['required', 'integer', 'exists:rombels,id'],
            'mata_pelajaran_id' => ['required', 'integer', 'exists:mata_pelajarans,id'],
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'tipe' => ['required', 'in:dokumen,video,link'],
            'file' => ['required_if:tipe,dokumen', 'required_if:tipe,video', 'nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,mp4,mov,avi,mp3'],
            'url' => ['required_if:tipe,link', 'nullable', 'url', 'max:500'],
            'is_aktif' => ['sometimes', 'boolean'],
        ];
    }
}
