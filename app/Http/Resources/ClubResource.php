<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClubResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'address' => $this->address,
            'phone' => $this->phone,
            'observation' => $this->observation,
            'resolution_id' => $this->resolution_id,
            'state_id' => $this->state_id,
            'place_sector_id' => $this->place_sector_id,
            'type_premises_id' => $this->type_premises_id,
            'state' => $this->whenLoaded('state', fn () => [
                'id' => $this->state->id,
                'title' => $this->state->title,
                'abbreviation' => $this->state->abbreviation,
            ]),
            'resolution' => $this->whenLoaded('resolution', fn () => $this->resolution
                ? new ReconocimientoResource($this->resolution)
                : null),
            'place_sector' => $this->whenLoaded('placeSector', fn () => $this->placeSector
                ? [
                    'id' => $this->placeSector->id,
                    'place' => $this->placeSector->place
                        ? ['id' => $this->placeSector->place->id, 'title' => $this->placeSector->place->title]
                        : null,
                    'sector' => $this->placeSector->sector
                        ? ['id' => $this->placeSector->sector->id, 'title' => $this->placeSector->sector->title]
                        : null,
                ]
                : null),
            'type_premises' => $this->whenLoaded('typePremises', fn () => $this->typePremises
                ? ['id' => $this->typePremises->id, 'title' => $this->typePremises->title]
                : null),
            'president_partner_id' => $this->president_partner_id ?? null,
            'president_name' => $this->president_name ?? null,
            'latest_resolution' => $this->latestResolution
                ? new ReconocimientoResource($this->latestResolution)
                : null,
            'all_resolutions' => isset($this->allResolutions)
                ? ReconocimientoResource::collection(collect($this->allResolutions))
                : null,
            'partners' => $this->whenLoaded('partners', fn () => $this->partners->map(fn ($partner) => [
                'id' => $partner->id,
                'name' => $partner->name,
                'dni' => $partner->people->dni ?? null,
            ])),
        ];
    }
}
