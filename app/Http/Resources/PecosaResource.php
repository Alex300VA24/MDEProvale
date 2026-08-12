<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PecosaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'pecosa_number' => $this->pecosa_number,
            'delivery_date' => $this->delivery_date,
            'observation' => $this->observation,
            'association' => $this->whenLoaded('association', fn () => [
                'id' => $this->association->id,
                'name' => $this->association->name,
                'code' => $this->association->code,
            ]),
            'state' => $this->whenLoaded('state', fn () => [
                'id' => $this->state->id,
                'title' => $this->state->title,
                'abbreviation' => $this->state->abbreviation,
            ]),
            'managing_partner' => $this->whenLoaded('managingPartner', fn () => [
                'id' => $this->managingPartner->id,
                'person' => $this->managingPartner->relationLoaded('people')
                    ? new PeopleResource($this->managingPartner->people)
                    : null,
            ]),
            'chief' => $this->whenLoaded('chief', fn () => [
                'id' => $this->chief->id,
                'person' => $this->chief->relationLoaded('person')
                    ? new PeopleResource($this->chief->person)
                    : null,
            ]),
            'storekeeper' => $this->whenLoaded('storekeeper', fn () => [
                'id' => $this->storekeeper->id,
                'person' => $this->storekeeper->relationLoaded('person')
                    ? new PeopleResource($this->storekeeper->person)
                    : null,
            ]),
            'president_name' => $this->president_name,
            'president_dni' => $this->president_dni,
            'chief_name' => $this->chief_name,
            'chief_dni' => $this->chief_dni,
            'storekeeper_name' => $this->storekeeper_name,
            'storekeeper_dni' => $this->storekeeper_dni,
            'managing_partner_name' => $this->managing_partner_name,
            'managing_partner_dni' => $this->managing_partner_dni,
            'association_name' => $this->association_name,
            'association_code' => $this->association_code,
            'association_address' => $this->association_address,
            'association_zone_code' => $this->association_zone_code,
            'association_zone_name' => $this->association_zone_name,
            'association_sector_name' => $this->association_sector_name,
            'beneficiaries_count' => $this->beneficiaries_count,
            'detail_pecosas' => DetailPecosaResource::collection($this->whenLoaded('detailPecosas')),
            'created_at' => $this->created_at,
        ];
    }
}
