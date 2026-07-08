<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', User::class);
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id ?? $this->route('id');
        return [
            'name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $userId,
            'password' => 'nullable|string|min:8|confirmed',
            'rol_id' => 'sometimes|required|exists:rols,id',
            'state_id' => 'sometimes|required|exists:states,id',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'El email ya está registrado.',
        ];
    }
}