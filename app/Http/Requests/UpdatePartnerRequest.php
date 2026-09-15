<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Partner;
use App\Models\State;
use Illuminate\Validation\Rule;

class UpdatePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('partner') ?? Partner::class);
    }

    public function rules(): array
    {
        return [
            'person_id' => 'sometimes|required|exists:people,id',
            'association_id' => 'sometimes|required|exists:associations,id',
            'state_id' => ['sometimes', 'required', Rule::exists('states', 'id')->where(fn ($q) => $q->whereIn('abbreviation', [State::CURRENT, State::EXPIRED]))],
            'date_begin' => 'sometimes|required|date',
            'date_end' => 'nullable|date|after_or_equal:date_begin',
            'observations' => 'nullable|string',
            'marital_status' => 'nullable|string|max:50',
            'education_level' => 'nullable|string|max:50',
            'occupation' => 'nullable|string|max:100',
            'children_count' => 'nullable|integer|min:0',
            'is_pregnant' => 'nullable|boolean',
            'is_lactating' => 'nullable|boolean',
            'spouse_occupation' => 'nullable|string|max:100',
            'spouse_education_level' => 'nullable|string|max:50',
            'family_income' => 'nullable|numeric|min:0',
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
            'beneficiaries.*.is_malnourished' => 'nullable|boolean',
            'beneficiaries.*.is_disabled' => 'nullable|boolean',
        ];
    }
}
