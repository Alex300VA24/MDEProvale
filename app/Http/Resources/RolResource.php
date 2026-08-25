<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RolResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'users_count' => $this->whenCounted('users'),
            'is_protected' => $this->id === 1,
            'modules' => $this->whenLoaded('modules', fn () => $this->modules->map(fn ($m) => [
                'id' => $m->id,
                'slug' => $m->slug,
                'name' => $m->name,
                'can_view' => (bool) $m->pivot->can_view,
                'can_create' => (bool) $m->pivot->can_create,
                'can_edit' => (bool) $m->pivot->can_edit,
                'can_delete' => (bool) $m->pivot->can_delete,
            ])),
        ];
    }
}
