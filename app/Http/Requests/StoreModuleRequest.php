<?php

namespace App\Http\Requests;

use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

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
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'route' => 'nullable|string|max:100',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ];
    }
}
