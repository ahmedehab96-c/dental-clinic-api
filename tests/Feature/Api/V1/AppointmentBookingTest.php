<?php

namespace Tests\Feature\Api\V1;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    /** A fixed weekday (never Friday, the clinic's closed day) in the future. */
    private function bookableDate(): string
    {
        return Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
    }

    public function test_guest_can_book_an_appointment(): void
    {
        $service = Service::factory()->create();
        $doctor = Doctor::factory()->create();

        $response = $this->postJson('/api/v1/appointments', [
            'service_id' => $service->id,
            'doctor_id' => $doctor->id,
            'date' => $this->bookableDate(),
            'time' => '09:00',
            'patient_name' => 'Ahmed Mohammed',
            'patient_phone' => '0551234567',
            'patient_email' => 'ahmed@example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.doctor.id', $doctor->id)
            ->assertJsonStructure(['data' => ['id', 'reference', 'patient' => ['name', 'phone', 'email']]]);

        $this->assertDatabaseHas('appointments', [
            'patient_email' => 'ahmed@example.com',
            'doctor_id' => $doctor->id,
        ]);
    }

    /**
     * Regression test: /appointments has no `auth:sanctum` middleware (it
     * must stay guest-accessible), so nothing calls Auth::shouldUse('sanctum')
     * for it. Resolving the booking user via plain $request->user() would
     * silently fall back to the app's default guard (session-based `web`)
     * and always store user_id as null — even for a genuinely authenticated
     * Sanctum request. See AppointmentController::store().
     */
    public function test_an_authenticated_users_booking_is_linked_to_their_account(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/appointments', [
            'service_id' => $service->id,
            'date' => $this->bookableDate(),
            'time' => '09:00',
            'patient_name' => 'Ahmed Mohammed',
            'patient_phone' => '0551234567',
            'patient_email' => 'ahmed@example.com',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('appointments', [
            'patient_email' => 'ahmed@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_booking_requires_core_fields(): void
    {
        $response = $this->postJson('/api/v1/appointments', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['service_id', 'date', 'time', 'patient_name', 'patient_phone', 'patient_email']);
    }

    public function test_booking_rejects_an_invalid_phone_format(): void
    {
        $service = Service::factory()->create();

        $response = $this->postJson('/api/v1/appointments', [
            'service_id' => $service->id,
            'date' => $this->bookableDate(),
            'time' => '09:00',
            'patient_name' => 'Ahmed',
            'patient_phone' => '123', // invalid format
            'patient_email' => 'ahmed@example.com',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['patient_phone']);
    }

    public function test_double_booking_the_same_doctor_slot_is_rejected(): void
    {
        $service = Service::factory()->create();
        $doctor = Doctor::factory()->create();
        $date = $this->bookableDate();

        Appointment::factory()->create([
            'service_id' => $service->id,
            'doctor_id' => $doctor->id,
            'date' => $date,
            'time' => '09:00',
            'status' => 'confirmed',
        ]);

        $response = $this->postJson('/api/v1/appointments', [
            'service_id' => $service->id,
            'doctor_id' => $doctor->id,
            'date' => $date,
            'time' => '09:00',
            'patient_name' => 'Another Patient',
            'patient_phone' => '0559876543',
            'patient_email' => 'another@example.com',
        ]);

        $response->assertStatus(409)->assertJsonPath('success', false);
    }

    public function test_no_preference_bookings_do_not_conflict_with_each_other(): void
    {
        $service = Service::factory()->create();
        $date = $this->bookableDate();

        Appointment::factory()->create([
            'service_id' => $service->id,
            'doctor_id' => null,
            'date' => $date,
            'time' => '09:00',
        ]);

        $response = $this->postJson('/api/v1/appointments', [
            'service_id' => $service->id,
            'doctor_id' => null,
            'date' => $date,
            'time' => '09:00',
            'patient_name' => 'Someone Else',
            'patient_phone' => '0559876543',
            'patient_email' => 'someone@example.com',
        ]);

        $response->assertCreated();
    }
}
