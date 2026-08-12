<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type_transaction_id' => $this->type_transaction_id,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total_price' => (float) $this->total_price,
            'document_number' => $this->document_number,
            'transaction_date' => $this->transaction_date?->toDateString(),
            'product_name' => $this->product_name,
            'uom_title' => $this->uom_title,
            'adjustment' => $this->adjustment !== null ? (float) $this->adjustment : null,
            'created_at' => $this->created_at?->toISOString(),
            'type' => $this->whenLoaded('typeTransaction', fn() => [
                'id' => $this->typeTransaction->id,
                'title' => $this->typeTransaction->title,
            ]),
            'detail_product' => $this->whenLoaded('detailProduct', fn() => [
                'id' => $this->detailProduct->id,
                'product_id' => $this->detailProduct->product_id,
                'quantity' => (float) $this->detailProduct->quantity,
                'unit_price' => (float) $this->detailProduct->unit_price,
                'start_date' => $this->detailProduct->start_date?->toDateString(),
                'end_date' => $this->detailProduct->end_date?->toDateString(),
                'product' => $this->whenLoaded('detailProduct.product', fn() => [
                    'id' => $this->detailProduct->product->id,
                    'title' => $this->detailProduct->product->title,
                    'abbreviation' => $this->detailProduct->product->abbreviation,
                ]),
            ]),
        ];
    }
}
