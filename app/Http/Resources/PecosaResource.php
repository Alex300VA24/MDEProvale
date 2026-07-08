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
            'association' => $this->whenLoaded('association', fn() => [
                'id' => $this->association->id,
                'name' => $this->association->name,
                'code' => $this->association->code,
            ]),
            'state' => $this->whenLoaded('state', fn() => [
                'id' => $this->state->id,
                'title' => $this->state->title,
                'abbreviation' => $this->state->abbreviation,
            ]),
            'managing_partner' => $this->whenLoaded('managingPartner', fn() => [
                'id' => $this->managingPartner->id,
                'person' => new PeopleResource($this->managingPartner->whenLoaded('people')),
            ]),
            'detail_pecosas' => $this->whenLoaded('detailPecosas', fn() => $this->detailPecosas->map(fn($dp) => [
                'id' => $dp->id,
                'quantity' => $dp->quantity,
                'unit_price' => $dp->unit_price,
                'subtotal' => $dp->subtotal,
                'product' => $dp->whenLoaded('detailProduct', fn() => [
                    'id' => $dp->detailProduct->product->id,
                    'title' => $dp->detailProduct->product->title,
                    'abbreviation' => $dp->detailProduct->product->abbreviation,
                ]),
            ])),
            'created_at' => $this->created_at,
        ];
    }
}