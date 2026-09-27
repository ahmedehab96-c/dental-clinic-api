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

class AdminServiceManagementTest extends TestCase
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
            'slug' => 'teeth-whitening',
            'name_ar' => 'تبييض الأسنان',
            'name_en' => 'Teeth Whitening',
            'short_description_ar' => 'وصف قصير',
            'short_description_en' => 'Short description',
            'description_ar' => 'وصف تفصيلي',
            'description_en' => 'Detailed description',
            'duration_ar' => '45 دقيقة',
            'duration_en' => '45 minutes',
            'price_from' => 350,
            'image' => UploadedFile::fake()->image('service.jpg'),
        ], $overrides);
    }

    public function test_admin_can_list_services_with_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        Service::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/services?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_can_search_services_by_name(): void
    {
        $admin = User::factory()->admin()->create();
        Service::factory()->create(['name_en' => 'Dental Implants']);
        Service::factory()->create(['name_en' => 'Root Canal Therapy']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/services?search=Implants')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name.en', 'Dental Implants');
    }

    public function test_admin_can_filter_services_by_active_status(): void
    {
        $admin = User::factory()->admin()->create();
        Service::factory()->create(['is_active' => true]);
        Service::factory()->inactive()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/services?is_active=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/services?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_list_returns_empty_state_cleanly(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/services')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_list_includes_assigned_doctors_count(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create();
        $doctors = Doctor::factory()->count(2)->create();
        $service->doctors()->attach($doctors);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/services')
            ->assertOk()
            ->assertJsonPath('data.0.doctors_count', 2);
    }

    public function test_admin_can_view_service_details_with_doctors(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create();
        $doctor = Doctor::factory()->create();
        $service->doctors()->attach($doctor);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/services/{$service->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', (string) $service->slug)
            ->assertJsonCount(1, 'data.doctors');
    }

    public function test_admin_can_create_a_service_with_doctors(): void
    {
        $admin = User::factory()->admin()->create();
        $doctors = Doctor::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/services', $this->validPayload([
            'doctor_ids' => $doctors->pluck('id')->all(),
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'teeth-whitening')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonCount(2, 'data.doctors');

        $service = Service::where('slug', 'teeth-whitening')->firstOrFail();
        Storage::disk('public')->assertExists($service->image_path);
    }

    public function test_creating_a_service_requires_an_image(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->validPayload();
        unset($payload['image']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/services', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_image_upload_rejects_non_image_files_and_oversized_files(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/services', $this->validPayload([
                'image' => UploadedFile::fake()->create('brochure.pdf', 100, 'application/pdf'),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/services', $this->validPayload([
                'image' => UploadedFile::fake()->image('big.jpg')->size(3000), // > 2MB
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_create_validates_required_fields_and_unique_slug(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/services', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'slug', 'name_ar', 'name_en', 'short_description_ar', 'short_description_en',
                'description_ar', 'description_en', 'duration_ar', 'duration_en', 'price_from', 'image',
            ]);

        Service::factory()->create(['slug' => 'teeth-whitening']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/services', $this->validPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_admin_can_update_a_service_and_preserve_image_when_none_uploaded(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create(['image_path' => 'services/original.jpg']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/services/{$service->slug}", ['name_en' => 'Updated Name'])
            ->assertOk()
            ->assertJsonPath('data.name.en', 'Updated Name');

        $this->assertSame('services/original.jpg', $service->fresh()->image_path);
    }

    public function test_admin_can_replace_a_services_image_and_the_old_one_is_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('services/old.jpg', 'fake-contents');
        $service = Service::factory()->create(['image_path' => 'services/old.jpg']);

        // Multipart update: POST + _method=PATCH (Laravel's spoofing convention),
        // since PHP never populates $_FILES for a raw PUT/PATCH body.
        $this->actingAs($admin, 'sanctum')
            ->post("/api/v1/admin/services/{$service->slug}", [
                '_method' => 'PATCH',
                'image' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertOk();

        $service->refresh();
        Storage::disk('public')->assertExists($service->image_path);
        Storage::disk('public')->assertMissing('services/old.jpg');
        $this->assertNotSame('services/old.jpg', $service->image_path);
    }

    public function test_admin_can_assign_and_reassign_doctors(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create();
        [$doctorA, $doctorB] = Doctor::factory()->count(2)->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/services/{$service->slug}", ['doctor_ids' => [$doctorA->id]])
            ->assertOk()
            ->assertJsonCount(1, 'data.doctors');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/services/{$service->slug}", ['doctor_ids' => [$doctorB->id]])
            ->assertOk()
            ->assertJsonCount(1, 'data.doctors')
            ->assertJsonPath('data.doctors.0.id', $doctorB->id);
    }

    public function test_admin_can_deactivate_and_reactivate_a_service(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create(['is_active' => true]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/services/{$service->slug}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        // A deactivated service drops off the public site entirely.
        $this->getJson('/api/v1/services')->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/services/{$service->slug}")->assertNotFound();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/services/{$service->slug}", ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->getJson('/api/v1/services')->assertJsonCount(1, 'data');
    }

    public function test_admin_cannot_delete_a_service_with_appointment_history(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create();
        Appointment::factory()->create(['service_id' => $service->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/services/{$service->slug}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    public function test_admin_can_delete_a_service_with_no_appointment_history(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create(['image_path' => 'services/to-delete.jpg']);
        Storage::disk('public')->put('services/to-delete.jpg', 'fake-contents');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/services/{$service->slug}")
            ->assertOk();

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
        Storage::disk('public')->assertMissing('services/to-delete.jpg');
    }

    public function test_non_admins_cannot_manage_services(): void
    {
        $patient = User::factory()->create();
        $doctorUser = User::factory()->doctor()->create();
        $service = Service::factory()->create();

        foreach ([$patient, $doctorUser] as $user) {
            $this->actingAs($user, 'sanctum')->getJson('/api/v1/admin/services')->assertStatus(403);
            $this->actingAs($user, 'sanctum')->postJson('/api/v1/admin/services', $this->validPayload())->assertStatus(403);
            $this->actingAs($user, 'sanctum')
                ->patchJson("/api/v1/admin/services/{$service->slug}", ['name_en' => 'Hacked'])
                ->assertStatus(403);
            $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/admin/services/{$service->slug}")->assertStatus(403);
        }

        $this->assertDatabaseMissing('services', ['name_en' => 'Hacked']);
    }

    public function test_guests_cannot_access_admin_service_endpoints(): void
    {
        $service = Service::factory()->create();

        $this->getJson('/api/v1/admin/services')->assertStatus(401);
        $this->postJson('/api/v1/admin/services', $this->validPayload())->assertStatus(401);
        $this->patchJson("/api/v1/admin/services/{$service->slug}", ['name_en' => 'X'])->assertStatus(401);
        $this->deleteJson("/api/v1/admin/services/{$service->slug}")->assertStatus(401);
    }
}
