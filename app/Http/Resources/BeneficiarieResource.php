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
                    'type_benefit' => $h->typeBenefit
                        ? ['id' => $h->typeBenefit->id, 'title' => $h->typeBenefit->title, 'abbreviation' => $h->typeBenefit->abbreviation]
                        : null,
                    'state' => $h->state ? ['id' => $h->state->id, 'title' => $h->state->title] : null,
                    'reason_disqualification' => $h->reasonDisqualification
                        ? ['id' => $h->reasonDisqualification->id, 'title' => $h->reasonDisqualification->title]
                        : null,
                ]);
            }),
        ];
    }
}
