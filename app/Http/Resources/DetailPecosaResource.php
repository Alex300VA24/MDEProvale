<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DetailPecosaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'priority' => $this->priority,
            'quantity' => $this->quantity,
            'delivered_quantity' => $this->delivered_quantity,
            'unit_price' => $this->unit_price,
            'subtotal' => $this->subtotal,
            'detail_product_id' => $this->detail_product_id,
            'product_name' => $this->product_name,
            'product_abbreviation' => $this->product_abbreviation,
            'uom_title' => $this->uom_title,
            'product' => $this->whenLoaded('detailProduct', fn () => $this->detailProduct?->product ? [
                'id' => $this->detailProduct->product->id,
                'title' => $this->detailProduct->product->title,
                'abbreviation' => $this->detailProduct->product->abbreviation,
            ] : null),
        ];
    }
}
