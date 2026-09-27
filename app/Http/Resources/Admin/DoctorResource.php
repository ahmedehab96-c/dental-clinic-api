<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * The admin-facing doctor shape — includes contact details (email, phone)
 * and account/activity fields the public DoctorResource deliberately never
 * exposes to site visitors.
 */
class DoctorResource extends ApiResource
{
    /**
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
            'email' => $this->email,
            'phone' => $this->phone,
            'photo_url' => $this->fileUrl($this->photo_path),
            'experience_years' => $this->experience_years,
            'rating' => (float) $this->rating,
            'reviews_count' => $this->reviews_count,
            'education' => $this->education,
            'featured' => $this->featured,
            'is_active' => $this->is_active,
            'user_id' => $this->user_id,
            'services' => $this->whenLoaded('services', fn () => $this->services->map(fn ($service) => [
                'id' => $service->id,
                'slug' => $service->slug,
                'name' => ['ar' => $service->name_ar, 'en' => $service->name_en],
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
