<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DetailProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $today = now()->toDateString();

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_title' => $this->product->title ?? null,
            'product_abbreviation' => $this->product->abbreviation ?? null,
            'uom_title' => $this->product->uom->title ?? null,
            'unit_price' => (float) $this->unit_price,
            'quantity' => (float) $this->quantity,
            'used_quantity' => (float) ($this->used_quantity ?? 0),
            'available_stock' => (float) ($this->quantity - ($this->used_quantity ?? 0)),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'active' => $this->end_date !== null && $this->end_date->toDateString() >= $today,
        ];
    }
}
