<?php

namespace Tests\Feature\Api\V1\Doctor;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function doctorUser(): array
    {
        $user = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create(['user_id' => $user->id]);

        return [$user, $doctor];
    }

    public function test_doctor_can_view_their_own_profile(): void
    {
        [$user, $doctor] = $this->doctorUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/doctor/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $doctor->id);
    }

    public function test_doctor_without_a_linked_profile_gets_a_clean_404(): void
    {
        $user = User::factory()->doctor()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/doctor/profile')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_doctor_can_view_their_assigned_services(): void
    {
        [$user, $doctor] = $this->doctorUser();
        $service = Service::factory()->create();
        $doctor->services()->attach($service);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/doctor/services')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $service->id);
    }

    public function test_doctor_can_view_and_update_status_of_their_own_appointment(): void
    {
        [$user, $doctor] = $this->doctorUser();
        $appointment = Appointment::factory()->create(['doctor_id' => $doctor->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/doctor/appointments')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/doctor/appointments/{$appointment->id}/status", ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'confirmed']);
    }

    public function test_doctor_cannot_update_status_of_another_doctors_appointment(): void
    {
        [$user] = $this->doctorUser();
        $otherDoctor = Doctor::factory()->create();
        $appointment = Appointment::factory()->create(['doctor_id' => $otherDoctor->id]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/doctor/appointments/{$appointment->id}/status", ['status' => 'confirmed'])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'pending']);
    }

    public function test_doctor_appointment_list_only_shows_their_own(): void
    {
        [$user, $doctor] = $this->doctorUser();
        $otherDoctor = Doctor::factory()->create();

        Appointment::factory()->create(['doctor_id' => $doctor->id]);
        Appointment::factory()->create(['doctor_id' => $otherDoctor->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/doctor/appointments')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_doctor_cannot_reach_admin_crud(): void
    {
        [$user] = $this->doctorUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/doctors', [])
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/admin/users/1')
            ->assertStatus(403);
    }

    public function test_a_patient_cannot_reach_doctor_endpoints(): void
    {
        $patient = User::factory()->create();

        $this->actingAs($patient, 'sanctum')
            ->getJson('/api/v1/doctor/appointments')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_doctor_endpoints_reject_unauthenticated_requests(): void
    {
        $this->getJson('/api/v1/doctor/profile')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }
}
