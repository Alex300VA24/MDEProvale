<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Partner;
use App\Models\State;
use App\Models\People;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Services\UploadSettingsService;

class UpdatePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('partner') ?? Partner::class);
    }

    public function rules(): array
    {
        $maxFile = app(UploadSettingsService::class)->maxKilobytes();

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
            'socioeconomic_condition' => ['nullable', Rule::in(['GESTANTE', 'LACTANTE', 'ADULTO_MAYOR', 'DISCAPACITADO', 'OTROS'])],
            'is_pregnant' => 'nullable|boolean',
            'is_lactating' => 'nullable|boolean',
            'family_income' => 'nullable|numeric|min:0',
            'fum' => 'required_if:socioeconomic_condition,GESTANTE|nullable|date|before_or_equal:today',
            'benefit_child_person_id' => 'nullable|exists:people,id',
            'family_document' => "nullable|file|mimes:pdf|max:{$maxFile}",
            'conadis_document' => "nullable|file|mimes:pdf|max:{$maxFile}",
            'beneficiaries' => 'nullable|array',
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
            if ($this->filled('benefit_child_person_id')) {
                $childPersonId = (int) $this->input('benefit_child_person_id');
                $belongs = $this->route('partner')?->beneficiaries()->where('person_id', $childPersonId)->exists();
                if (! $childPersonId || ! $belongs) {
                    $validator->errors()->add('benefit_child_person_id', 'Seleccione un beneficiario perteneciente a esta ficha.');
                }
            }

            if ($this->input('socioeconomic_condition') === 'DISCAPACITADO'
                && ! $this->hasFile('conadis_document')
                && ! $this->route('partner')?->documents()->where('document_type', 'conadis')->exists()) {
                $validator->errors()->add('conadis_document', 'Debe adjuntar el documento CONADIS en PDF.');
            }

            if ($this->input('socioeconomic_condition') !== 'ADULTO_MAYOR') {
                return;
            }

            $personId = $this->input('person_id', $this->route('partner')?->person_id);
            $birthdate = People::find($personId)?->birthdate;
            if (! $birthdate || Carbon::parse($birthdate)->age <= 65) {
                $validator->errors()->add('socioeconomic_condition', 'Adulto Mayor requiere ser mayor de 65 años.');
            }
        });
    }
}
