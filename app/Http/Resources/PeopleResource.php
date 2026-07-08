<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PeopleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_name' => trim("{$this->names} {$this->father_lastname} {$this->mother_lastname}"),
            'names' => $this->names,
            'father_lastname' => $this->father_lastname,
            'mother_lastname' => $this->mother_lastname,
            'dni' => $this->dni,
        ];
    }
}