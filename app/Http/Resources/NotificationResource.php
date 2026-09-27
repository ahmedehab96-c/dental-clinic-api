<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * A stored database notification. `data` is the structured payload written
 * by the notification class; the frontend localizes it. The notifiable
 * morph columns and the PHP class name are internal and never returned.
 */
class NotificationResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->data['kind'] ?? null,
            'data' => $this->data,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
