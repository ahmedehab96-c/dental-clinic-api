<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'slug' => str($name)->slug(),
            'icon' => 'checkup',
            'name_ar' => $name,
            'name_en' => $name,
            'short_description_ar' => fake()->sentence(),
            'short_description_en' => fake()->sentence(),
            'description_ar' => fake()->paragraph(),
            'description_en' => fake()->paragraph(),
            'image_path' => fake()->imageUrl(),
            'duration_ar' => '30 دقيقة',
            'duration_en' => '30 minutes',
            'price_from' => fake()->numberBetween(100, 3000),
            'features' => [
                ['ar' => fake()->words(3, true), 'en' => fake()->words(3, true)],
            ],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
