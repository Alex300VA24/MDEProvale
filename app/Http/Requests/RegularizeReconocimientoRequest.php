<?php

namespace App\Http\Requests;

use App\Models\Resolution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class RegularizeReconocimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('resolution') ?? Resolution::class);
    }

    public function rules(): array
    {
        return [
            'document' => ['required', 'string', 'max:100', 'regex:/^\d+-\d{4}$/'],
            'date_start' => ['sometimes', 'nullable', 'date'],
            'date_end' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_start'],
        ];
    }

    public function messages(): array
    {
        return [
            'document.required' => 'Ingrese el número de resolución.',
            'document.regex' => 'El número de resolución debe tener el formato NNN-AAAA (ej. 123-2026).',
        ];
    }
}
