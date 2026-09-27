<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ServiceResource extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
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
            'doctors' => $this->whenLoaded('doctors', fn () => $this->doctors->map(fn ($doctor) => [
                'id' => $doctor->id,
                'slug' => $doctor->slug,
                'name' => ['ar' => $doctor->name_ar, 'en' => $doctor->name_en],
            ])),
        ];
    }
}
