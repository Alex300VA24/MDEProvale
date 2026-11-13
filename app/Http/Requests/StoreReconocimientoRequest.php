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
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'pending' => 'sometimes|boolean',
            'observation' => 'required_if:pending,true,1|nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'document.required' => 'El documento de la resolución es obligatorio.',
            'observation.required_if' => 'La observación es obligatoria cuando la resolución queda Pendiente.',
            'date_end.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
        ];
    }
}
