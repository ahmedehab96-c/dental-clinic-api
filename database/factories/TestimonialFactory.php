<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'patient_name_ar' => $name,
            'patient_name_en' => $name,
            'role_ar' => 'مريض',
            'role_en' => 'Patient',
            'photo_path' => fake()->imageUrl(200, 200),
            'rating' => fake()->numberBetween(4, 5),
            'quote_ar' => fake()->sentence(),
            'quote_en' => fake()->sentence(),
            'is_published' => true,
        ];
    }
}
