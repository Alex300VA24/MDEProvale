<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResponsibleHistoryResource extends JsonResource
{
    public function toArray($request)
    {
        $start = $this->start_date ?? $this->created_at;
        $end = $this->end_date;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'active' => (bool) $this->active,
            'person_id' => $this->person_id,
            'person_name' => $this->whenLoaded('person', fn () => trim(
                "{$this->person->names} {$this->person->father_lastname} {$this->person->mother_lastname}"
            )),
            'person_dni' => $this->whenLoaded('person', fn () => $this->person->dni),
            'start_date' => optional($start)->toIso8601String(),
            'end_date' => optional($end)->toIso8601String(),
            'duration_days' => $start ? $start->copy()->startOfDay()->diffInDays(($end ?? now())->copy()->startOfDay()) : null,
        ];
    }
}
