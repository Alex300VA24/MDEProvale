<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Resolution;

class StoreResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Resolution::class);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'number' => 'required|string|max:50|unique:resolutions,number',
            'date' => 'required|date',
            'state_id' => 'required|exists:states,id',
        ];
    }

    public function messages(): array
    {
        return [
            'number.unique' => 'El número de resolución ya está registrado.',
        ];
    }
}