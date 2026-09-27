<?php

namespace Tests\Feature\Api\V1\Patient;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_update_their_own_profile(): void
    {
        $patient = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($patient, 'sanctum')
            ->patchJson('/api/v1/me', ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('users', ['id' => $patient->id, 'name' => 'New Name']);
    }

    public function test_patient_cannot_set_their_own_role_via_profile_update(): void
    {
        $patient = User::factory()->create();

        $this->actingAs($patient, 'sanctum')
            ->patchJson('/api/v1/me', ['name' => 'Still Patient', 'role' => 'admin']);

        $this->assertDatabaseHas('users', ['id' => $patient->id, 'role' => 'patient']);
    }

    public function test_patient_can_view_and_cancel_their_own_appointment(): void
    {
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $patient->id, 'status' => 'pending']);

        $this->actingAs($patient, 'sanctum')
            ->getJson("/api/v1/me/appointments/{$appointment->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $appointment->id);

        $this->actingAs($patient, 'sanctum')
            ->postJson("/api/v1/me/appointments/{$appointment->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'cancelled']);
    }

    public function test_patient_cannot_cancel_a_completed_appointment(): void
    {
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $patient->id, 'status' => 'completed']);

        $this->actingAs($patient, 'sanctum')
            ->postJson("/api/v1/me/appointments/{$appointment->id}/cancel")
            ->assertStatus(403);

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'completed']);
    }

    public function test_patient_cannot_view_or_cancel_another_patients_appointment(): void
    {
        $patient = User::factory()->create();
        $otherPatient = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $otherPatient->id]);

        $this->actingAs($patient, 'sanctum')
            ->getJson("/api/v1/me/appointments/{$appointment->id}")
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->actingAs($patient, 'sanctum')
            ->postJson("/api/v1/me/appointments/{$appointment->id}/cancel")
            ->assertStatus(403);

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'pending']);
    }

    public function test_patient_appointment_list_only_shows_their_own(): void
    {
        $patient = User::factory()->create();
        $otherPatient = User::factory()->create();

        Appointment::factory()->create(['user_id' => $patient->id]);
        Appointment::factory()->create(['user_id' => $otherPatient->id]);
        Appointment::factory()->create(['user_id' => null]);

        $this->actingAs($patient, 'sanctum')
            ->getJson('/api/v1/me/appointments')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_patient_cannot_reach_doctor_or_admin_endpoints(): void
    {
        $patient = User::factory()->create();

        $this->actingAs($patient, 'sanctum')
            ->getJson('/api/v1/doctor/profile')
            ->assertStatus(403);

        $this->actingAs($patient, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertStatus(403);
    }

    public function test_me_endpoints_reject_unauthenticated_requests(): void
    {
        $this->patchJson('/api/v1/me', ['name' => 'Someone'])
            ->assertStatus(401)
            ->assertJsonPath('success', false);

        $this->getJson('/api/v1/me/appointments')
            ->assertStatus(401);
    }
}
