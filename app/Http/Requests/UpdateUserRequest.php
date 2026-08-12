<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('usuario'));
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'names' => 'required|string|max:150',
            'father_surname' => 'required|string|max:100',
            'mother_surname' => 'required|string|max:100',
            'username' => ['required', 'string', 'max:100', Rule::unique('users')->ignore($usuario->id)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($usuario->id)],
            'dni' => ['required', 'string', 'size:8', Rule::unique('users')->ignore($usuario->id)],
            'cui' => 'nullable|string|max:1',
            'rol_id' => 'required|exists:rols,id',
            'state_id' => 'required|exists:states,id',
            'password' => 'nullable|string|min:8',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $usuario = $this->route('usuario');
            $authUser = $this->user();

            if (
                $authUser
                && $usuario
                && $authUser->id === $usuario->id
                && (int) $this->input('rol_id') !== (int) $usuario->rol_id
            ) {
                $validator->errors()->add('rol_id', 'No puedes cambiar tu propio rol.');
            }
        });
    }
}
