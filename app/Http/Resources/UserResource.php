<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'names' => $this->names,
            'father_surname' => $this->father_surname,
            'mother_surname' => $this->mother_surname,
            'full_name' => trim("{$this->names} {$this->father_surname} {$this->mother_surname}"),
            'username' => $this->username,
            'email' => $this->email,
            'dni' => $this->dni,
            'cui' => $this->cui,
            'rol_id' => $this->rol_id,
            'state_id' => $this->state_id,
            'rol' => $this->whenLoaded('rol', fn () => [
                'id' => $this->rol->id,
                'title' => $this->rol->title,
            ]),
            'state' => $this->whenLoaded('state', fn () => [
                'id' => $this->state->id,
                'title' => $this->state->title,
            ]),
            'is_self' => $request->user() && $request->user()->id === $this->id,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
