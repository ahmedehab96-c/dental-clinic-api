<?php

namespace Database\Factories;

use App\Models\GalleryCase;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryCase>
 */
class GalleryCaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title_ar' => $title,
            'title_en' => $title,
            'service_id' => Service::factory(),
            'before_image_path' => fake()->imageUrl(700, 700),
            'after_image_path' => fake()->imageUrl(700, 700),
            'is_published' => true,
        ];
    }
}
