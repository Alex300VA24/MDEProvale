<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Partner;
use App\Models\State;
use Illuminate\Validation\Rule;

class StorePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Partner::class);
    }

    public function rules(): array
    {
        return [
            'person_id' => 'required|exists:people,id',
            'association_id' => 'required|exists:associations,id',
            'state_id' => ['required', Rule::exists('states', 'id')->where(fn ($q) => $q->whereIn('abbreviation', [State::CURRENT, State::EXPIRED]))],
            'date_begin' => 'required|date',
            'date_end' => 'nullable|date|after_or_equal:date_begin',
            'observations' => 'nullable|string',
            'beneficiaries' => 'nullable|array',
            'beneficiaries.*.person_id' => 'required|exists:people,id',
            'beneficiaries.*.relationship_id' => 'required|exists:relationships,id',
            'beneficiaries.*.type_benefit_id' => 'nullable|exists:type_benefits,id',
            'beneficiaries.*.history_state_id' => ['nullable', Rule::exists('states', 'id')->where(fn ($q) => $q->whereIn('abbreviation', [State::CURRENT, State::EXPIRED]))],
            'beneficiaries.*.date_begin' => 'nullable|date',
            'beneficiaries.*.date_end' => 'nullable|date|after_or_equal:beneficiaries.*.date_begin',
            'beneficiaries.*.weight' => 'nullable|numeric|min:0',
            'beneficiaries.*.height' => 'nullable|numeric|min:0',
            'beneficiaries.*.hmg' => 'nullable|numeric|min:0',
            'beneficiaries.*.reason_disqualification_id' => 'nullable|exists:reason_disqualifications,id',
        ];
    }

    public function messages(): array
    {
        return [
            'person_id.required' => 'La persona es obligatoria.',
            'person_id.exists' => 'La persona seleccionada no existe.',
            'association_id.required' => 'La asociación es obligatoria.',
            'association_id.exists' => 'La asociación seleccionada no existe.',
            'date_begin.required' => 'La fecha de inicio es obligatoria.',
        ];
    }
}
