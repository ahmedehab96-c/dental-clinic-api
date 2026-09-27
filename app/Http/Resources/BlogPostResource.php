<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class BlogPostResource extends ApiResource
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
            'category' => [
                'ar' => $this->category->label_ar,
                'en' => $this->category->label_en,
            ],
            'title' => $this->localized('title'),
            'excerpt' => $this->localized('excerpt'),
            'content' => [
                'ar' => $this->content_ar,
                'en' => $this->content_en,
            ],
            'image_url' => $this->fileUrl($this->image_path),
            'author' => $this->author ? [
                'ar' => $this->author->name_ar,
                'en' => $this->author->name_en,
            ] : null,
            'date' => optional($this->published_at)->format('Y-m-d'),
            'read_minutes' => $this->read_minutes,
        ];
    }
}
