<?php

namespace App\Http\Requests;

use App\Models\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Rol::class);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100|unique:rols,title',
            'description' => 'nullable|string|max:255',
            'modules' => 'nullable|array',
            'modules.*' => 'array',
            'modules.*.can_view' => 'nullable|boolean',
            'modules.*.can_create' => 'nullable|boolean',
            'modules.*.can_edit' => 'nullable|boolean',
            'modules.*.can_delete' => 'nullable|boolean',
        ];
    }
}
