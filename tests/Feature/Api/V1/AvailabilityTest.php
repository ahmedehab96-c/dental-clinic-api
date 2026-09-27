<?php

namespace Tests\Feature\Api\V1;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_a_date_it_returns_the_next_bookable_dates(): void
    {
        $response = $this->getJson('/api/v1/availability');

        $response->assertOk()->assertJsonStructure(['data' => ['dates']]);
        $this->assertNotEmpty($response->json('data.dates'));
    }

    public function test_the_clinic_is_closed_on_fridays(): void
    {
        $nextFriday = Carbon::now()->next(Carbon::FRIDAY)->format('Y-m-d');

        $response = $this->getJson("/api/v1/availability?date={$nextFriday}");

        $response->assertOk()->assertJsonPath('data', []);
    }

    public function test_a_booked_slot_is_marked_unavailable_for_that_doctor(): void
    {
        $doctor = Doctor::factory()->create();
        $service = Service::factory()->create();
        $date = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'date' => $date,
            'time' => '09:00',
            'status' => 'confirmed',
        ]);

        $response = $this->getJson("/api/v1/availability?date={$date}&doctor_id={$doctor->id}");

        $response->assertOk();
        $slots = collect($response->json('data'));
        $this->assertFalse($slots->firstWhere('time', '09:00')['available']);
        $this->assertTrue($slots->firstWhere('time', '10:00')['available']);
    }
}
