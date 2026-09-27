<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'slug', 'name_ar', 'name_en', 'specialty_ar', 'specialty_en', 'bio_ar', 'bio_en',
    'email', 'phone', 'photo_path', 'experience_years', 'rating', 'reviews_count', 'education',
    'featured', 'is_active',
])]
class Doctor extends Model
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory, HasSlug;

    protected function casts(): array
    {
        return [
            'education' => 'array',
            'featured' => 'boolean',
            'is_active' => 'boolean',
            'rating' => 'decimal:1',
        ];
    }

    /**
     * The doctor-role account this clinical profile is linked to. Nullable
     * — a doctor profile can exist (e.g. seeded content) before it's linked.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return HasMany<BlogPost, $this>
     */
    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'author_doctor_id');
    }
}
