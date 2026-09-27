<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TestimonialResource extends ApiResource
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
            'name' => $this->localized('patient_name'),
            'role' => $this->localized('role'),
            'photo_url' => $this->fileUrl($this->photo_path),
            'rating' => $this->rating,
            'quote' => $this->localized('quote'),
        ];
    }
}
