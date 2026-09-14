<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100', 'unique:menus,name'],
            'icon' => ['nullable', 'string', 'max:100'],
            'route' => ['nullable', 'string', 'max:100'],
            'url' => ['nullable', 'url'],
            'permission' => ['nullable', 'string', 'exists:permissions,name'],
            'parent_id' => ['nullable', 'integer', 'exists:menus,id'],
            'order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'is_header' => ['required', 'boolean'],
            'group' => ['nullable', 'string', 'max:50'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
