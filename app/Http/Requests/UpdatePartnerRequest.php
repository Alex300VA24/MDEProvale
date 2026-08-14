<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Partner;

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
            'state_id' => 'sometimes|required|exists:states,id',
            'date_begin' => 'sometimes|required|date',
            'date_end' => 'nullable|date|after_or_equal:date_begin',
            'observations' => 'nullable|string',
            'beneficiaries' => 'nullable|array',
            'beneficiaries.*.person_id' => 'required|exists:people,id',
            'beneficiaries.*.relationship_id' => 'required|exists:relationships,id',
            'beneficiaries.*.type_benefit_id' => 'nullable|exists:type_benefits,id',
            'beneficiaries.*.history_state_id' => 'nullable|exists:states,id',
            'beneficiaries.*.date_begin' => 'nullable|date',
            'beneficiaries.*.date_end' => 'nullable|date|after_or_equal:beneficiaries.*.date_begin',
            'beneficiaries.*.weight' => 'nullable|numeric|min:0',
            'beneficiaries.*.height' => 'nullable|numeric|min:0',
            'beneficiaries.*.hmg' => 'nullable|numeric|min:0',
            'beneficiaries.*.reason_disqualification_id' => 'nullable|exists:reason_disqualifications,id',
        ];
    }
}