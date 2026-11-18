<?php

namespace App\Http\Requests;

use App\Models\Module;
use App\Support\ModuleCatalog;
use Illuminate\Contracts\Validation\Validator;
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
        /** @var Module $module */
        $module = $this->route('modulo');

        return [
            'name' => 'required|string|max:100',
            'slug' => ['required', 'string', 'max:100', Rule::unique('modules')->ignore($module->id)],
            // Solo hay un nivel de submódulos: el padre debe ser un módulo de primer nivel distinto de este.
            'parent_id' => [
                'nullable',
                'integer',
                Rule::notIn([$module->id]),
                Rule::exists('modules', 'id')->whereNull('parent_id'),
            ],
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
            'parent_id.not_in' => 'Un módulo no puede ser su propio padre.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Module $module */
            $module = $this->route('modulo');

            // Los módulos del catálogo están cableados en rutas, políticas y menú.
            if (ModuleCatalog::isBuiltIn($module->slug)) {
                if ($this->input('slug') !== $module->slug) {
                    $validator->errors()->add('slug', 'El slug de un módulo del sistema no se puede cambiar.');
                }

                $expectedParent = ModuleCatalog::parentOf($module->slug);
                $parentId = $this->input('parent_id');
                $parentSlug = $parentId ? Module::whereKey($parentId)->value('slug') : null;
                if ($parentSlug !== $expectedParent) {
                    $validator->errors()->add('parent_id', 'No se puede cambiar el módulo padre de un módulo del sistema.');
                }
            }

            // Un grupo con submódulos no puede pasar a ser submódulo (máximo un nivel).
            if ($this->filled('parent_id') && $module->children()->exists()) {
                $validator->errors()->add('parent_id', 'Este módulo tiene submódulos, por lo que no puede depender de otro.');
            }
        });
    }
}
