<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAppointmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_appointments_with_pagination_meta(): void
    {
        $admin = User::factory()->admin()->create();
        Appointment::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/appointments?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_admin_list_returns_a_clean_empty_state(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/appointments')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_can_filter_appointments_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        Appointment::factory()->create(['status' => 'pending']);
        Appointment::factory()->create(['status' => 'confirmed']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/appointments?status=confirmed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'confirmed');
    }

    public function test_admin_can_filter_appointments_by_doctor_and_service(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $service = Service::factory()->create();
        Appointment::factory()->create(['doctor_id' => $doctor->id, 'service_id' => $service->id]);
        Appointment::factory()->create(); // unrelated doctor/service

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/appointments?doctor_id={$doctor->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/appointments?service_id={$service->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_search_appointments_by_patient_name_or_phone(): void
    {
        $admin = User::factory()->admin()->create();
        Appointment::factory()->create(['patient_name' => 'Ahmed Mohammed', 'patient_phone' => '0551234567']);
        Appointment::factory()->create(['patient_name' => 'Sara Al-Fahad', 'patient_phone' => '0559876543']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/appointments?search=Ahmed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.patient.name', 'Ahmed Mohammed');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/appointments?search=0559876543')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.patient.name', 'Sara Al-Fahad');
    }

    public function test_admin_can_view_appointment_details_including_notes_and_created_at(): void
    {
        $admin = User::factory()->admin()->create();
        $appointment = Appointment::factory()->create(['notes' => 'Patient requested morning slot.']);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/appointments/{$appointment->id}")
            ->assertOk()
            ->assertJsonPath('data.notes', 'Patient requested morning slot.')
            ->assertJsonStructure(['data' => ['id', 'reference', 'status', 'created_at', 'patient' => ['name', 'phone', 'email']]]);
    }

    public function test_admin_can_confirm_cancel_and_complete_an_appointment(): void
    {
        $admin = User::factory()->admin()->create();
        $appointment = Appointment::factory()->create(['status' => 'pending']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/appointments/{$appointment->id}/status", ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/appointments/{$appointment->id}/status", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $other = Appointment::factory()->create(['status' => 'pending']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/appointments/{$other->id}/status", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_status_update_rejects_an_invalid_status_value(): void
    {
        $admin = User::factory()->admin()->create();
        $appointment = Appointment::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/appointments/{$appointment->id}/status", ['status' => 'archived'])
            ->assertStatus(422);
    }

    public function test_non_admins_cannot_list_view_or_update_appointments(): void
    {
        $patient = User::factory()->create();
        $doctor = User::factory()->doctor()->create();
        $appointment = Appointment::factory()->create();

        foreach ([$patient, $doctor] as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/v1/admin/appointments')
                ->assertStatus(403);

            $this->actingAs($user, 'sanctum')
                ->getJson("/api/v1/admin/appointments/{$appointment->id}")
                ->assertStatus(403);
        }

        // A doctor may update the status of their OWN appointment via the
        // doctor route, but never through the admin one.
        $this->actingAs($doctor, 'sanctum')
            ->patchJson("/api/v1/admin/appointments/{$appointment->id}/status", ['status' => 'confirmed'])
            ->assertStatus(403);
    }

    public function test_guests_cannot_access_admin_appointments(): void
    {
        $appointment = Appointment::factory()->create();

        $this->getJson('/api/v1/admin/appointments')->assertStatus(401);
        $this->getJson("/api/v1/admin/appointments/{$appointment->id}")->assertStatus(401);
        $this->patchJson("/api/v1/admin/appointments/{$appointment->id}/status", ['status' => 'confirmed'])->assertStatus(401);
    }
}
