<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'is_seen' => (bool) $this->is_seen,
            'requested_at' => $this->requested_at?->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),
            'requested_by_name' => $this->whenLoaded('requestedByUser', fn () => $this->requestedByUser?->names),
            'processed_by_name' => $this->whenLoaded('processedByUser', fn () => $this->processedByUser?->names),
        ];
    }
}
