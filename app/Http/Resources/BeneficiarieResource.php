<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BeneficiarieResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'person_id' => $this->person_id,
            'partner_id' => $this->partner_id,
            'relationship_id' => $this->relationship_id,
            'name' => $this->name,
            'person' => $this->whenLoaded('person', function () {
                return [
                    'id' => $this->person->id,
                    'names' => $this->person->names,
                    'father_lastname' => $this->person->father_lastname,
                    'mother_lastname' => $this->person->mother_lastname,
                    'dni' => $this->person->dni,
                    'birthdate' => $this->person->birthdate,
                    'gender' => $this->person->gender,
                    'address' => $this->person->address,
                    'age_formatted' => $this->person->age_formatted,
                ];
            }),
            'partner' => $this->whenLoaded('partner', function () {
                return [
                    'id' => $this->partner->id,
                    'name' => $this->partner->name,
                ];
            }),
            'relationship' => $this->whenLoaded('relationship', function () {
                return [
                    'id' => $this->relationship->id,
                    'title' => $this->relationship->title,
                ];
            }),
            'histories' => $this->whenLoaded('histories', function () {
                return $this->histories->map(fn ($h) => [
                    'id' => $h->id,
                    'weight' => $h->weight,
                    'height' => $h->height,
                    'hmg' => $h->hmg,
                    'date_begin' => $h->date_begin,
                    'date_end' => $h->date_end,
                    'type_benefit_id' => $h->type_benefit_id,
                    'state_id' => $h->state_id,
                    'reason_disqualification_id' => $h->reason_disqualification_id,
                    'is_malnourished' => (bool) $h->is_malnourished,
                    'is_disabled' => (bool) $h->is_disabled,
                    'type_benefit' => $h->typeBenefit
                        ? ['id' => $h->typeBenefit->id, 'title' => $h->typeBenefit->title, 'abbreviation' => $h->typeBenefit->abbreviation]
                        : null,
                    'state' => $h->state ? ['id' => $h->state->id, 'title' => $h->state->title] : null,
                    'reason_disqualification' => $h->reasonDisqualification
                        ? ['id' => $h->reasonDisqualification->id, 'title' => $h->reasonDisqualification->title]
                        : null,
                ]);
            }),
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
