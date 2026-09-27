<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_admin_can_view_dashboard_stats(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->create(); // patients
        $doctors = Doctor::factory()->count(3)->create();
        $services = Service::factory()->count(4)->create();
        Appointment::factory()->create([
            'status' => 'pending', 'date' => today(), 'doctor_id' => $doctors->first()->id, 'service_id' => $services->first()->id,
        ]);
        Appointment::factory()->create([
            'status' => 'confirmed', 'doctor_id' => $doctors->first()->id, 'service_id' => $services->first()->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_patients', 2)
            ->assertJsonPath('data.total_doctors', 3)
            ->assertJsonPath('data.total_services', 4)
            ->assertJsonPath('data.today_appointments', 1)
            ->assertJsonPath('data.pending_appointments', 1)
            ->assertJsonPath('data.confirmed_appointments', 1);
    }

    public function test_dashboard_stats_are_forbidden_for_non_admins(): void
    {
        $patient = User::factory()->create();
        $doctor = User::factory()->doctor()->create();

        $this->actingAs($patient, 'sanctum')->getJson('/api/v1/admin/dashboard')->assertStatus(403);
        $this->actingAs($doctor, 'sanctum')->getJson('/api/v1/admin/dashboard')->assertStatus(403);
    }

    public function test_dashboard_stats_reject_unauthenticated_requests(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(401);
    }

    public function test_admin_can_create_a_doctor(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/doctors', [
            'name_ar' => 'دكتور',
            'name_en' => 'Dr. New',
            'specialty_ar' => 'أسنان',
            'specialty_en' => 'Dentist',
            'bio_ar' => 'نبذة',
            'bio_en' => 'Bio',
            'email' => 'dr.new@example.com',
            'phone' => '0551234567',
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertCreated()->assertJsonPath('data.slug', 'dr-new');
        $this->assertDatabaseHas('doctors', ['slug' => 'dr-new', 'email' => 'dr.new@example.com']);
    }

    public function test_admin_can_update_and_delete_a_doctor(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/doctors/{$doctor->slug}", ['name_en' => 'Updated Name'])
            ->assertOk()
            ->assertJsonPath('data.name.en', 'Updated Name');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/doctors/{$doctor->slug}")
            ->assertOk();

        $this->assertDatabaseMissing('doctors', ['id' => $doctor->id]);
    }

    public function test_admin_can_manage_services(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/services', [
            'slug' => 'whitening',
            'icon' => 'sparkle',
            'name_ar' => 'تبييض',
            'name_en' => 'Whitening',
            'short_description_ar' => 'وصف قصير',
            'short_description_en' => 'Short desc',
            'description_ar' => 'وصف',
            'description_en' => 'Description',
            'image' => UploadedFile::fake()->image('service.jpg'),
            'duration_ar' => '30 دقيقة',
            'duration_en' => '30 minutes',
            'price_from' => 500,
        ]);

        $response->assertCreated();
        $service = Service::where('slug', 'whitening')->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/services/{$service->slug}")
            ->assertOk();

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_admin_can_view_and_manage_all_appointments(): void
    {
        $admin = User::factory()->admin()->create();
        Appointment::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/appointments')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $appointment = Appointment::first();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/appointments/{$appointment->id}/status", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_admin_can_manage_users(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/users/{$patient->id}", ['role' => 'doctor'])
            ->assertOk()
            ->assertJsonPath('data.role', 'doctor');

        $this->assertDatabaseHas('users', ['id' => $patient->id, 'role' => 'doctor']);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$admin->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_a_doctor_cannot_reach_admin_endpoints(): void
    {
        $doctor = User::factory()->doctor()->create();

        $this->actingAs($doctor, 'sanctum')
            ->postJson('/api/v1/admin/services', [])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->actingAs($doctor, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertStatus(403);
    }

    public function test_a_patient_cannot_reach_admin_endpoints(): void
    {
        $patient = User::factory()->create();

        $this->actingAs($patient, 'sanctum')
            ->getJson('/api/v1/admin/appointments')
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->actingAs($patient, 'sanctum')
            ->postJson('/api/v1/admin/doctors', [])
            ->assertStatus(403);
    }

    public function test_admin_endpoints_reject_unauthenticated_requests(): void
    {
        $this->getJson('/api/v1/admin/appointments')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }
}
