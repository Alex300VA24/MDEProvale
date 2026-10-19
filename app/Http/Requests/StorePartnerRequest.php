<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Partner;
use App\Models\State;
use App\Models\People;
use Illuminate\Support\Carbon;
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
            'marital_status' => 'nullable|string|max:50',
            'education_level' => 'nullable|string|max:50',
            'occupation' => 'nullable|string|max:100',
            'children_count' => 'nullable|integer|min:0',
            'socioeconomic_condition' => ['nullable', Rule::in(['GESTANTE', 'LACTANTE', 'ADULTO_MAYOR', 'DISCAPACITADO', 'OTROS'])],
            'is_pregnant' => 'nullable|boolean',
            'is_lactating' => 'nullable|boolean',
            'family_income' => 'nullable|numeric|min:0',
            'family_document' => 'nullable|file|mimes:pdf|max:10240',
            'conadis_document' => 'required_if:socioeconomic_condition,DISCAPACITADO|file|mimes:pdf|max:10240',
            'beneficiary_documents' => 'nullable|array',
            'beneficiary_documents.*' => 'nullable|file|mimes:pdf|max:10240',
            'beneficiaries' => 'required|array|min:1',
            'beneficiaries.*.person_id' => 'required|exists:people,id',
            'beneficiaries.*.relationship_id' => 'required|exists:relationships,id',
            'beneficiaries.*.priority' => 'nullable|integer|in:1,2',
            'beneficiaries.*.type_benefit_id' => 'nullable|exists:type_benefits,id',
            'beneficiaries.*.history_state_id' => ['nullable', Rule::exists('states', 'id')->where(fn ($q) => $q->whereIn('abbreviation', [State::CURRENT, State::EXPIRED]))],
            'beneficiaries.*.date_begin' => 'nullable|date',
            'beneficiaries.*.date_end' => 'nullable|date|after_or_equal:beneficiaries.*.date_begin',
            'beneficiaries.*.reason_disqualification_id' => 'nullable|exists:reason_disqualifications,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('socioeconomic_condition') !== 'ADULTO_MAYOR') {
                return;
            }

            $birthdate = People::find($this->input('person_id'))?->birthdate;
            if (! $birthdate || Carbon::parse($birthdate)->age <= 65) {
                $validator->errors()->add('socioeconomic_condition', 'Adulto Mayor requiere ser mayor de 65 años.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'person_id.required' => 'La persona es obligatoria.',
            'person_id.exists' => 'La persona seleccionada no existe.',
            'association_id.required' => 'La asociación es obligatoria.',
            'association_id.exists' => 'La asociación seleccionada no existe.',
            'date_begin.required' => 'La fecha de inicio es obligatoria.',
            'beneficiaries.required' => 'Debe registrar al menos un beneficiario junto con el socio.',
            'beneficiaries.min' => 'Debe registrar al menos un beneficiario junto con el socio.',
        ];
    }
}
