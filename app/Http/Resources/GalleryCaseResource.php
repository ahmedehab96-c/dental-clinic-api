<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class GalleryCaseResource extends ApiResource
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
            'title' => $this->localized('title'),
            'category' => $this->whenLoaded('service', fn () => $this->service->slug),
            'before_image_url' => $this->fileUrl($this->before_image_path),
            'after_image_url' => $this->fileUrl($this->after_image_path),
        ];
    }
}
