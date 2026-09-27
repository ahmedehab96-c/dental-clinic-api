<?php

namespace Database\Factories;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Dr. '.fake()->name();

        return [
            'slug' => str($name)->slug(),
            'name_ar' => $name,
            'name_en' => $name,
            'specialty_ar' => 'طبيب أسنان عام',
            'specialty_en' => 'General Dentist',
            'bio_ar' => fake()->paragraph(),
            'bio_en' => fake()->paragraph(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '05'.fake()->numerify('########'),
            'photo_path' => fake()->imageUrl(),
            'experience_years' => fake()->numberBetween(1, 20),
            'rating' => fake()->randomFloat(1, 4, 5),
            'reviews_count' => fake()->numberBetween(10, 400),
            'education' => [
                ['ar' => 'بكالوريوس طب وجراحة الفم والأسنان', 'en' => 'BDS'],
            ],
            'featured' => false,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
