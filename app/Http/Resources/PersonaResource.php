<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PersonaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'names' => $this->names,
            'father_lastname' => $this->father_lastname,
            'mother_lastname' => $this->mother_lastname,
            'dni' => $this->dni,
            'gender' => $this->gender,
            'gender_label' => match ($this->gender) {
                'M' => 'Masculino',
                'F' => 'Femenino',
                default => null,
            },
            'telephone_number' => $this->telephone_number,
            'phone_number' => $this->phone_number,
            'birthdate' => $this->birthdate,
            'address' => $this->address,
            'place_sector_id' => $this->place_sector_id,
            'age_formatted' => $this->age_formatted,
            'place_sector' => $this->whenLoaded('placeSector', function () {
                return [
                    'id' => $this->placeSector->id,
                    'place_id' => $this->placeSector->place_id,
                    'sector_id' => $this->placeSector->sector_id,
                    'place_title' => $this->placeSector->place->title ?? null,
                    'sector_title' => $this->placeSector->sector->title ?? null,
                ];
            }),
        ];
    }
}
