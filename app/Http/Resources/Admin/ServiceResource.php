<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * The admin-facing service shape — adds `is_active` and the full list of
 * assigned doctors, which the public ServiceResource deliberately never
 * exposes as a manageable field to site visitors.
 */
class ServiceResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'name' => $this->localized('name'),
            'short_description' => $this->localized('short_description'),
            'description' => $this->localized('description'),
            'image_url' => $this->fileUrl($this->image_path),
            'duration' => $this->localized('duration'),
            'price_from' => $this->price_from,
            'features' => $this->features,
            'is_active' => $this->is_active,
            'doctors' => $this->whenLoaded('doctors', fn () => $this->doctors->map(fn ($doctor) => [
                'id' => $doctor->id,
                'slug' => $doctor->slug,
                'name' => ['ar' => $doctor->name_ar, 'en' => $doctor->name_en],
            ])),
            // The list view (`withCount`) only needs the number; the details
            // view (`with('doctors')`) needs the full roster — support both
            // without requiring the caller to eager-load doctors just to count them.
            'doctors_count' => $this->relationLoaded('doctors')
                ? $this->doctors->count()
                : $this->doctors_count,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
