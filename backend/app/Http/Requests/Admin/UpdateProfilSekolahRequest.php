<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilSekolahRequest extends FormRequest
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
            'nama_sekolah' => ['required', 'string', 'max:255'],
            'npsn' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'telp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'tahun_berdiri' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')],
            'akreditasi' => ['nullable', 'in:A,B,C'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'foto_gedung' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'sambutan' => ['nullable', 'string', 'max:5000'],
            'visi' => ['nullable', 'string', 'max:5000'],
            'misi' => ['nullable', 'array', 'max:20'],
            'misi.*' => ['required_with:misi', 'string', 'max:500'],
            'nama_kepala' => ['nullable', 'string', 'max:255'],
            'foto_kepala' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'maps_embed_url' => ['nullable', 'string', 'max:2000', 'starts_with:https://www.google.com/maps/embed'],
        ];
    }
}
