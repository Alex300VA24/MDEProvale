<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'route' => $this->route,
            'order' => $this->order,
            'is_active' => (bool) $this->is_active,
            'is_protected' => $this->slug === 'sistema',
        ];
    }
}
