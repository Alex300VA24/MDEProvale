<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RacionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'year' => (int) $this->year,
            'racion_hojuelas_gramos' => (float) $this->racion_hojuelas_gramos,
            'racion_leche_militros' => (float) $this->racion_leche_militros,
            'active' => (bool) $this->active,
        ];
    }
}
