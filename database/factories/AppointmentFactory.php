<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'APT-'.strtoupper(Str::random(6)),
            'service_id' => Service::factory(),
            'doctor_id' => Doctor::factory(),
            'user_id' => null,
            'patient_name' => fake()->name(),
            'patient_phone' => '05'.fake()->numerify('########'),
            'patient_email' => fake()->safeEmail(),
            'date' => fake()->dateTimeBetween('+1 day', '+2 weeks')->format('Y-m-d'),
            'time' => fake()->randomElement(['09:00', '10:00', '11:00', '14:00', '16:00']),
            'notes' => null,
            'status' => 'pending',
        ];
    }
}
