<?php

namespace App\Http\Requests;

use App\Models\Association;
use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AsignarCargosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('association') ?? Association::class);
    }

    public function rules(): array
    {
        return [
            'assignments' => ['required', 'array', 'min:1', 'max:' . count(Position::COMMITTEE_TITLES)],
            'assignments.*.partner_id' => ['required', 'integer', 'exists:partners,id'],
            'assignments.*.position' => [
                'required',
                'string',
                'distinct',
                Rule::in(Position::COMMITTEE_TITLES),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'assignments.required' => 'Debe asignar por lo menos un cargo.',
            'assignments.*.partner_id.required' => 'Debe seleccionar una socia.',
            'assignments.*.partner_id.exists' => 'Una de las socias seleccionadas no existe.',
            'assignments.*.position.distinct' => 'No puede repetir un cargo.',
            'assignments.*.position.in' => 'Uno de los cargos seleccionados no es válido.',
        ];
    }
}
