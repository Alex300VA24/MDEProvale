<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Resolution;

class StoreReconocimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Resolution::class);
    }

    public function rules(): array
    {
        return [
            'document' => 'required|string|max:100',
            'date_document' => 'required|date',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'state_id' => 'required|exists:states,id',
        ];
    }

    public function messages(): array
    {
        return [
            'document.required' => 'El documento de la resolución es obligatorio.',
            'date_end.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
        ];
    }
}
