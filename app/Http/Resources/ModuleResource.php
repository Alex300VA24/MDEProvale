<?php

namespace App\Http\Resources;

use App\Support\ModuleCatalog;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'parent_slug' => $this->whenLoaded('parent', fn () => $this->parent?->slug),
            'parent_name' => $this->whenLoaded('parent', fn () => $this->parent?->name),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'route' => $this->route,
            'order' => $this->order,
            'is_active' => (bool) $this->is_active,
            // Grupo = módulo con submódulos: no guarda permisos propios.
            'is_group' => $this->whenCounted('children', fn () => $this->children_count > 0),
            'is_protected' => ModuleCatalog::isBuiltIn($this->slug),
        ];
    }
}
