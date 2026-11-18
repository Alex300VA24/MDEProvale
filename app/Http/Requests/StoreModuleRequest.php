<?php

namespace App\Http\Requests;

use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Module::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:modules,slug',
            // Solo hay un nivel de submódulos: el padre debe ser un módulo de primer nivel.
            'parent_id' => ['nullable', 'integer', Rule::exists('modules', 'id')->whereNull('parent_id')],
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50|exists:module_icons,class_name',
            'route' => 'nullable|string|max:100',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.exists' => 'El módulo padre debe ser un módulo de primer nivel.',
        ];
    }
}
