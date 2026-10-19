<?php

namespace App\Http\Requests;

use App\Models\Association;
use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AsignarCargoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('association') ?? Association::class);
    }

    public function rules(): array
    {
        return [
            'partner_id' => ['required', 'integer', 'exists:partners,id'],
            'position' => ['required', 'string', Rule::in(Position::COMMITTEE_TITLES)],
        ];
    }

    public function messages(): array
    {
        return [
            'partner_id.required' => 'Debe seleccionar una socia.',
            'partner_id.exists' => 'La socia seleccionada no existe.',
            'position.required' => 'Debe seleccionar un cargo.',
            'position.in' => 'El cargo seleccionado no es válido.',
        ];
    }
}
