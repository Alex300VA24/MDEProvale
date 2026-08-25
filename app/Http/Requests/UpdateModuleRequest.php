<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('modulo'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'slug' => ['required', 'string', 'max:100', Rule::unique('modules')->ignore($this->route('modulo')->id)],
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50|exists:module_icons,class_name',
            'route' => 'nullable|string|max:100',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ];
    }
}
