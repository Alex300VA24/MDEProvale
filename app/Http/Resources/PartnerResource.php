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
            'name' => $this->name,
            'person' => $this->whenLoaded('people', function () {
                return [
                    'id' => $this->people->id,
                    'names' => $this->people->names,
                    'father_lastname' => $this->people->father_lastname,
                    'mother_lastname' => $this->people->mother_lastname,
                    'dni' => $this->people->dni,
                    'address' => $this->people->address,
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
        ];
    }
}
