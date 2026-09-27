<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_ar' => fake()->sentence().'؟',
            'question_en' => fake()->sentence().'?',
            'answer_ar' => fake()->paragraph(),
            'answer_en' => fake()->paragraph(),
            'sort_order' => fake()->numberBetween(0, 20),
            'is_published' => true,
        ];
    }
}
