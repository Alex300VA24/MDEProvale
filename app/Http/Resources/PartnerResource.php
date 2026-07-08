<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PartnerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'person' => new PeopleResource($this->whenLoaded('people')),
            'association' => $this->whenLoaded('association', fn() => [
                'id' => $this->association->id,
                'name' => $this->association->name,
                'code' => $this->association->code,
            ]),
            'state' => $this->whenLoaded('state', fn() => [
                'id' => $this->state->id,
                'title' => $this->state->title,
            ]),
            'date_begin' => $this->date_begin,
            'date_end' => $this->date_end,
            'beneficiaries_count' => $this->beneficiaries_count ?? $this->beneficiaries->count(),
            'created_at' => $this->created_at,
        ];
    }
}