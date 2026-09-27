<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a plain ['time' => '09:00', 'available' => true] array — computed by
 * AvailabilityService, not backed by a model — so it isn't in the ApiResource
 * base (no bilingual fields to compose here).
 */
class TimeSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'time' => $this->resource['time'],
            'available' => $this->resource['available'],
        ];
    }
}
