<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'names' => 'required|string|max:150',
            'father_surname' => 'required|string|max:100',
            'mother_surname' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'dni' => ['required', 'string', 'size:8', Rule::unique('users', 'dni')->ignore($userId)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)->numbers()->symbols()],
        ];
    }

    public function attributes(): array
    {
        return [
            'names' => 'nombres',
            'father_surname' => 'apellido paterno',
            'mother_surname' => 'apellido materno',
            'email' => 'correo',
            'dni' => 'DNI',
            'current_password' => 'contraseña actual',
            'password' => 'nueva contraseña',
        ];
    }
}
