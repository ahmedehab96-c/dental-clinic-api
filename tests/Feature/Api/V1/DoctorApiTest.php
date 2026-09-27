<?php

namespace Tests\Feature\Api\V1;

use App\Models\Doctor;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_doctors(): void
    {
        Doctor::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/doctors');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'slug', 'name' => ['ar', 'en'], 'specialty' => ['ar', 'en'], 'photo_url', 'rating']],
                'meta' => ['current_page', 'total'],
            ]);
    }

    public function test_index_can_filter_by_service_slug(): void
    {
        $service = Service::factory()->create(['slug' => 'teeth-whitening']);
        $matching = Doctor::factory()->create();
        $matching->services()->attach($service);
        Doctor::factory()->create(); // unrelated doctor

        $response = $this->getJson('/api/v1/doctors?service=teeth-whitening');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_show_returns_doctor_by_slug(): void
    {
        $doctor = Doctor::factory()->create(['slug' => 'dr-test']);

        $this->getJson('/api/v1/doctors/dr-test')
            ->assertOk()
            ->assertJsonPath('data.slug', 'dr-test');
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/v1/doctors/does-not-exist')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
