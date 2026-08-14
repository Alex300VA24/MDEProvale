<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', User::class);
    }

    public function rules(): array
    {
        return [
            'names' => 'required|string|max:150',
            'father_surname' => 'required|string|max:100',
            'mother_surname' => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'dni' => 'required|string|size:8|unique:users,dni',
            'cui' => 'nullable|string|max:1',
            'rol_id' => 'required|exists:rols,id',
            'state_id' => 'required|exists:states,id',
            'password' => ['required', 'string', Password::min(8)->numbers()->symbols()],
        ];
    }
}
