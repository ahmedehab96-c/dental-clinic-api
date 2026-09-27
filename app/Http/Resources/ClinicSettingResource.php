<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ClinicSettingResource extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'address' => $this->localized('address'),
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'working_hours' => $this->localized('working_hours'),
            'social_links' => $this->social_links,
            'default_og_image_url' => $this->fileUrl($this->default_og_image_path),
            'map' => [
                'lat' => $this->map_lat !== null ? (float) $this->map_lat : null,
                'lng' => $this->map_lng !== null ? (float) $this->map_lng : null,
            ],
        ];
    }
}
