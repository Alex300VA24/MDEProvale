<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'abbreviation' => $this->abbreviation,
            'code' => $this->code,
            'uom' => $this->whenLoaded('uom', fn() => [
                'id' => $this->uom->id,
                'title' => $this->uom->title,
            ]),
            'state' => $this->whenLoaded('state', fn() => [
                'id' => $this->state->id,
                'title' => $this->state->title,
            ]),
            'stock' => $this->stock,
        ];
    }
}