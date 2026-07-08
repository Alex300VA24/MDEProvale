<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\People;

class StorePersonaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', People::class);
    }

    public function rules(): array
    {
        return [
            'names' => 'required|string|max:100',
            'father_lastname' => 'required|string|max:100',
            'mother_lastname' => 'required|string|max:100',
            'dni' => 'required|string|max:8|unique:people,dni',
            'birthdate' => 'nullable|date',
            'gender' => 'nullable|in:M,F',
            'address' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'dni.unique' => 'El DNI ya está registrado.',
            'names.required' => 'Los nombres son obligatorios.',
            'father_lastname.required' => 'El apellido paterno es obligatorio.',
            'mother_lastname.required' => 'El apellido materno es obligatorio.',
        ];
    }
}