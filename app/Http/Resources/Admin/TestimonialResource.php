<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * The admin-facing testimonial shape — adds `is_published`, which the
 * public TestimonialResource deliberately never exposes since the public
 * endpoint only ever returns published testimonials in the first place.
 */
class TestimonialResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->localized('patient_name'),
            'role' => $this->localized('role'),
            'photo_url' => $this->fileUrl($this->photo_path),
            'rating' => $this->rating,
            'quote' => $this->localized('quote'),
            'is_published' => $this->is_published,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
