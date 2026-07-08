<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\People;

class UpdatePersonaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', People::class);
    }

    public function rules(): array
    {
        return [
            'names' => 'sometimes|required|string|max:100',
            'father_lastname' => 'sometimes|required|string|max:100',
            'mother_lastname' => 'sometimes|required|string|max:100',
            'dni' => 'sometimes|required|string|max:8|unique:people,dni,' . $this->route('person'),
            'birthdate' => 'nullable|date',
            'gender' => 'nullable|in:M,F',
            'address' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'dni.unique' => 'El DNI ya está registrado.',
        ];
    }
}