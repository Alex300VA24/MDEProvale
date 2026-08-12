<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Resolution;

class UpdateReconocimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('resolution') ?? Resolution::class);
    }

    public function rules(): array
    {
        return [
            'document' => 'sometimes|required|string|max:100',
            'date_document' => 'sometimes|required|date',
            'date_start' => 'sometimes|required|date',
            'date_end' => 'sometimes|required|date|after_or_equal:date_start',
            'state_id' => 'sometimes|required|exists:states,id',
        ];
    }

    public function messages(): array
    {
        return [
            'date_end.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
        ];
    }
}
