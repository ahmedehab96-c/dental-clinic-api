<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\BlogPostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'slug', 'category_id', 'author_doctor_id', 'title_ar', 'title_en',
    'excerpt_ar', 'excerpt_en', 'content_ar', 'content_en', 'image_path',
    'published_at', 'read_minutes',
])]
class BlogPost extends Model
{
    /** @use HasFactory<BlogPostFactory> */
    use HasFactory, HasSlug;

    protected function casts(): array
    {
        return [
            'content_ar' => 'array',
            'content_en' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BlogCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'author_doctor_id');
    }
}
