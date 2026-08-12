<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResponsibleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'active' => (bool) $this->active,
            'person_id' => $this->person_id,
            'person_name' => $this->whenLoaded('person', fn () => trim(
                "{$this->person->names} {$this->person->father_lastname} {$this->person->mother_lastname}"
            )),
            'person_dni' => $this->whenLoaded('person', fn () => $this->person->dni),
        ];
    }
}
