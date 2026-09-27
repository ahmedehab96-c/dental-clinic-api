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

class AdminDoctorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name_ar' => 'د. أحمد',
            'name_en' => 'Dr. Ahmed',
            'specialty_ar' => 'طبيب أسنان',
            'specialty_en' => 'Dentist',
            'bio_ar' => 'نبذة عن الطبيب',
            'bio_en' => 'Doctor bio',
            'email' => 'dr.ahmed@example.com',
            'phone' => '0551234567',
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ], $overrides);
    }

    public function test_admin_can_list_doctors_with_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        Doctor::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/doctors?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_can_search_doctors_by_name_or_specialty(): void
    {
        $admin = User::factory()->admin()->create();
        Doctor::factory()->create(['name_en' => 'Dr. Sara Alamri', 'specialty_en' => 'Orthodontics']);
        Doctor::factory()->create(['name_en' => 'Dr. Omar Hassan', 'specialty_en' => 'Implants']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/doctors?search=Sara')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name.en', 'Dr. Sara Alamri');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/doctors?search=Implants')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name.en', 'Dr. Omar Hassan');
    }

    public function test_admin_can_filter_doctors_by_active_status(): void
    {
        $admin = User::factory()->admin()->create();
        Doctor::factory()->create(['is_active' => true]);
        Doctor::factory()->inactive()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/doctors?is_active=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/doctors?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_list_returns_empty_state_cleanly(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/doctors')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_can_view_doctor_details_with_services(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        $service = Service::factory()->create();
        $doctor->services()->attach($service);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/doctors/{$doctor->slug}")
            ->assertOk()
            ->assertJsonPath('data.email', $doctor->email)
            ->assertJsonCount(1, 'data.services');
    }

    public function test_admin_can_create_a_doctor_with_services_and_auto_generated_slug(): void
    {
        $admin = User::factory()->admin()->create();
        $services = Service::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/doctors', $this->validPayload([
            'name_en' => 'Dr. Layla Test',
            'service_ids' => $services->pluck('id')->all(),
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'dr-layla-test')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonCount(2, 'data.services');

        $doctor = Doctor::where('slug', 'dr-layla-test')->firstOrFail();
        Storage::disk('public')->assertExists($doctor->photo_path);
    }

    public function test_creating_a_doctor_requires_a_photo(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->validPayload();
        unset($payload['photo']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/doctors', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_photo_upload_rejects_non_image_files_and_oversized_files(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/doctors', $this->validPayload([
                'photo' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/doctors', $this->validPayload([
                'photo' => UploadedFile::fake()->image('big.jpg')->size(3000), // > 2MB
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_create_validates_required_fields_and_formats(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/doctors', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name_ar', 'name_en', 'specialty_ar', 'specialty_en', 'bio_ar', 'bio_en', 'email', 'phone', 'photo']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/doctors', $this->validPayload(['email' => 'not-an-email']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/doctors', $this->validPayload(['phone' => '12345']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_admin_can_update_a_doctor_and_preserve_photo_when_none_uploaded(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create(['photo_path' => 'doctors/original.jpg']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/doctors/{$doctor->slug}", ['name_en' => 'Updated Name'])
            ->assertOk()
            ->assertJsonPath('data.name.en', 'Updated Name');

        $this->assertSame('doctors/original.jpg', $doctor->fresh()->photo_path);
    }

    public function test_admin_can_replace_a_doctors_photo_and_the_old_one_is_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('doctors/old.jpg', 'fake-contents');
        $doctor = Doctor::factory()->create(['photo_path' => 'doctors/old.jpg']);

        // Multipart update: POST + _method=PATCH (Laravel's spoofing convention),
        // since PHP never populates $_FILES for a raw PUT/PATCH body.
        $this->actingAs($admin, 'sanctum')
            ->post("/api/v1/admin/doctors/{$doctor->slug}", [
                '_method' => 'PATCH',
                'photo' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertOk();

        $doctor->refresh();
        Storage::disk('public')->assertExists($doctor->photo_path);
        Storage::disk('public')->assertMissing('doctors/old.jpg');
        $this->assertNotSame('doctors/old.jpg', $doctor->photo_path);
    }

    public function test_admin_can_assign_and_reassign_services(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        [$serviceA, $serviceB] = Service::factory()->count(2)->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/doctors/{$doctor->slug}", ['service_ids' => [$serviceA->id]])
            ->assertOk()
            ->assertJsonCount(1, 'data.services');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/doctors/{$doctor->slug}", ['service_ids' => [$serviceB->id]])
            ->assertOk()
            ->assertJsonCount(1, 'data.services')
            ->assertJsonPath('data.services.0.id', $serviceB->id);
    }

    public function test_admin_can_deactivate_and_reactivate_a_doctor(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create(['is_active' => true]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/doctors/{$doctor->slug}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        // A deactivated doctor drops off the public site entirely.
        $this->getJson('/api/v1/doctors')->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/doctors/{$doctor->slug}")->assertNotFound();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/doctors/{$doctor->slug}", ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->getJson('/api/v1/doctors')->assertJsonCount(1, 'data');
    }

    public function test_admin_cannot_delete_a_doctor_with_appointment_history(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create();
        Appointment::factory()->create(['doctor_id' => $doctor->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/doctors/{$doctor->slug}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('doctors', ['id' => $doctor->id]);
    }

    public function test_admin_can_delete_a_doctor_with_no_appointment_history(): void
    {
        $admin = User::factory()->admin()->create();
        $doctor = Doctor::factory()->create(['photo_path' => 'doctors/to-delete.jpg']);
        Storage::disk('public')->put('doctors/to-delete.jpg', 'fake-contents');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/doctors/{$doctor->slug}")
            ->assertOk();

        $this->assertDatabaseMissing('doctors', ['id' => $doctor->id]);
        Storage::disk('public')->assertMissing('doctors/to-delete.jpg');
    }

    public function test_non_admins_cannot_manage_doctors(): void
    {
        $patient = User::factory()->create();
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create();

        foreach ([$patient, $doctorUser] as $user) {
            $this->actingAs($user, 'sanctum')->getJson('/api/v1/admin/doctors')->assertStatus(403);
            $this->actingAs($user, 'sanctum')->postJson('/api/v1/admin/doctors', $this->validPayload())->assertStatus(403);
            $this->actingAs($user, 'sanctum')
                ->patchJson("/api/v1/admin/doctors/{$doctor->slug}", ['name_en' => 'Hacked'])
                ->assertStatus(403);
            $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/admin/doctors/{$doctor->slug}")->assertStatus(403);
        }

        $this->assertDatabaseMissing('doctors', ['name_en' => 'Hacked']);
    }

    public function test_guests_cannot_access_admin_doctor_endpoints(): void
    {
        $doctor = Doctor::factory()->create();

        $this->getJson('/api/v1/admin/doctors')->assertStatus(401);
        $this->postJson('/api/v1/admin/doctors', $this->validPayload())->assertStatus(401);
        $this->patchJson("/api/v1/admin/doctors/{$doctor->slug}", ['name_en' => 'X'])->assertStatus(401);
        $this->deleteJson("/api/v1/admin/doctors/{$doctor->slug}")->assertStatus(401);
    }
}
