<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PartnerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'person_id' => $this->person_id,
            'association_id' => $this->association_id,
            'state_id' => $this->state_id,
            'date_begin' => $this->date_begin,
            'date_end' => $this->date_end,
            'observations' => $this->observations,
            'marital_status' => $this->marital_status,
            'education_level' => $this->education_level,
            'occupation' => $this->occupation,
            'children_count' => $this->children_count,
            'is_pregnant' => (bool) $this->is_pregnant,
            'is_lactating' => (bool) $this->is_lactating,
            'spouse_occupation' => $this->spouse_occupation,
            'spouse_education_level' => $this->spouse_education_level,
            'family_income' => $this->family_income,
            'name' => $this->name,
            'person' => $this->whenLoaded('people', function () {
                return [
                    'id' => $this->people->id,
                    'names' => $this->people->names,
                    'father_lastname' => $this->people->father_lastname,
                    'mother_lastname' => $this->people->mother_lastname,
                    'dni' => $this->people->dni,
                    'birthdate' => $this->people->birthdate,
                    'gender' => $this->people->gender,
                    'phone_number' => $this->people->phone_number,
                    'address' => $this->people->address,
                    'place_sector_id' => $this->people->place_sector_id,
                ];
            }),
            'association' => $this->whenLoaded('association', function () {
                return [
                    'id' => $this->association->id,
                    'name' => $this->association->name,
                    'code' => $this->association->code,
                ];
            }),
            'state' => $this->whenLoaded('state', function () {
                return [
                    'id' => $this->state->id,
                    'title' => $this->state->title,
                ];
            }),
            'beneficiaries_count' => $this->whenCounted('beneficiaries'),
            'beneficiaries' => BeneficiarieResource::collection($this->whenLoaded('beneficiaries')),
            'documents' => $this->whenLoaded('documents', function () {
                return $this->documents->map(fn ($doc) => [
                    'id' => $doc->id,
                    'document_type' => $doc->document_type,
                    'file_name' => $doc->file_name,
                    'mime_type' => $doc->mime_type,
                    'file_size' => $doc->file_size,
                    'created_at' => $doc->created_at?->toISOString(),
                    'url' => route('api.socios-beneficiarios.documents.show', $doc->id),
                ]);
            }),
        ];
    }
}
