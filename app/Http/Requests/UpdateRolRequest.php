<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('rol'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100', Rule::unique('rols')->ignore($this->route('rol')->id)],
            'description' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
            'modules' => 'nullable|array',
            'modules.*' => 'array',
            'modules.*.can_view' => 'nullable|boolean',
            'modules.*.can_create' => 'nullable|boolean',
            'modules.*.can_edit' => 'nullable|boolean',
            'modules.*.can_delete' => 'nullable|boolean',
        ];
    }
}
