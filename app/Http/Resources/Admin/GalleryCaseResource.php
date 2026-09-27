<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * The admin-facing gallery case shape — includes `is_published` and the
 * full linked service (id + slug + localized name) the public
 * GalleryCaseResource deliberately simplifies to just the service's slug.
 */
class GalleryCaseResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->localized('title'),
            'service' => $this->whenLoaded('service', fn () => [
                'id' => $this->service->id,
                'slug' => $this->service->slug,
                'name' => ['ar' => $this->service->name_ar, 'en' => $this->service->name_en],
            ]),
            'before_image_url' => $this->fileUrl($this->before_image_path),
            'after_image_url' => $this->fileUrl($this->after_image_path),
            'is_published' => $this->is_published,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
