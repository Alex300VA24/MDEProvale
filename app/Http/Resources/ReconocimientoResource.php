<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReconocimientoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'document' => $this->document,
            'date_document' => $this->date_document?->toDateTimeString(),
            'date_start' => $this->date_start?->toDateString(),
            'date_end' => $this->date_end?->toDateString(),
            'state_id' => $this->state_id,
            'state' => $this->whenLoaded('state', fn () => [
                'id' => $this->state->id,
                'title' => $this->state->title,
                'abbreviation' => $this->state->abbreviation,
            ]),
            'associations_count' => $this->associations_count ?? null,
        ];
    }
}
