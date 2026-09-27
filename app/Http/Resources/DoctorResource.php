<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class DoctorResource extends ApiResource
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
            'name' => $this->localized('name'),
            'specialty' => $this->localized('specialty'),
            'bio' => $this->localized('bio'),
            'photo_url' => $this->fileUrl($this->photo_path),
            'experience_years' => $this->experience_years,
            'rating' => (float) $this->rating,
            'reviews_count' => $this->reviews_count,
            'education' => $this->education,
            'featured' => $this->featured,
            'services' => $this->whenLoaded('services', fn () => $this->services->map(fn ($service) => [
                'id' => $service->id,
                'slug' => $service->slug,
                'name' => ['ar' => $service->name_ar, 'en' => $service->name_en],
            ])),
        ];
    }
}
