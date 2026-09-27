<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug', 'icon', 'name_ar', 'name_en', 'short_description_ar', 'short_description_en',
    'description_ar', 'description_en', 'image_path', 'duration_ar', 'duration_en',
    'price_from', 'features', 'is_active',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasSlug;

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'price_from' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Doctor, $this>
     */
    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class);
    }

    /**
     * @return HasMany<GalleryCase, $this>
     */
    public function galleryCases(): HasMany
    {
        return $this->hasMany(GalleryCase::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
