<?php

namespace Database\Factories;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence();

        return [
            'slug' => str($title)->slug(),
            'category_id' => BlogCategory::factory(),
            'author_doctor_id' => null,
            'title_ar' => $title,
            'title_en' => $title,
            'excerpt_ar' => fake()->sentence(),
            'excerpt_en' => fake()->sentence(),
            'content_ar' => fake()->paragraphs(3),
            'content_en' => fake()->paragraphs(3),
            'image_path' => fake()->imageUrl(),
            'published_at' => now(),
            'read_minutes' => fake()->numberBetween(2, 8),
        ];
    }
}
