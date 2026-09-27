<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * The admin-facing post shape — includes the raw `category_id` (for form
 * binding), a derived `status`, and the raw `published_at` timestamp the
 * public PostResource deliberately simplifies to a display-only `date`.
 */
class BlogPostResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'key' => $this->category->key,
                'label' => ['ar' => $this->category->label_ar, 'en' => $this->category->label_en],
            ]),
            'title' => $this->localized('title'),
            'excerpt' => $this->localized('excerpt'),
            'content' => [
                'ar' => $this->content_ar,
                'en' => $this->content_en,
            ],
            'image_url' => $this->fileUrl($this->image_path),
            'author' => $this->whenLoaded('author', fn () => $this->author ? [
                'id' => $this->author->id,
                'name' => ['ar' => $this->author->name_ar, 'en' => $this->author->name_en],
            ] : null),
            // Binary from the admin's point of view — a post either has a
            // publish timestamp or it doesn't. A future timestamp still
            // reads as "published" here; the public index is what actually
            // withholds it from the site until that moment arrives.
            'status' => $this->published_at ? 'published' : 'draft',
            'published_at' => $this->published_at?->toIso8601String(),
            'read_minutes' => $this->read_minutes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
