<?php

namespace App\DTOs;

class StockDTO extends BaseDTO
{
    public int $quantity;
    public float $unit_price;
    public float $total;

    public function __construct(
        int $quantity,
        float $unit_price,
        float $total
    ) {
        $this->quantity = $quantity;
        $this->unit_price = $unit_price;
        $this->total = $total;
    }
}