<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTestimonialManagementTest extends TestCase
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
            'patient_name_ar' => 'أحمد محمد',
            'patient_name_en' => 'Ahmed Mohamed',
            'role_ar' => 'مريض',
            'role_en' => 'Patient',
            'rating' => 5,
            'quote_ar' => 'تجربة رائعة',
            'quote_en' => 'Great experience',
            'photo' => UploadedFile::fake()->image('patient.jpg'),
        ], $overrides);
    }

    public function test_admin_can_list_testimonials_with_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        Testimonial::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/testimonials?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_list_includes_unpublished_testimonials(): void
    {
        $admin = User::factory()->admin()->create();
        Testimonial::factory()->create(['is_published' => false]);
        Testimonial::factory()->create(['is_published' => true]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/testimonials')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_search_testimonials_by_name_or_quote(): void
    {
        $admin = User::factory()->admin()->create();
        Testimonial::factory()->create(['patient_name_en' => 'Sara Alamri', 'quote_en' => 'Loved it']);
        Testimonial::factory()->create(['patient_name_en' => 'Omar Hassan', 'quote_en' => 'Great service']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/testimonials?search=Sara')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name.en', 'Sara Alamri');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/testimonials?search=Great service')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name.en', 'Omar Hassan');
    }

    public function test_admin_can_filter_testimonials_by_rating(): void
    {
        $admin = User::factory()->admin()->create();
        Testimonial::factory()->create(['rating' => 5]);
        Testimonial::factory()->create(['rating' => 4]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/testimonials?rating=5')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rating', 5);
    }

    public function test_admin_can_filter_testimonials_by_published_status(): void
    {
        $admin = User::factory()->admin()->create();
        Testimonial::factory()->create(['is_published' => true]);
        Testimonial::factory()->create(['is_published' => false]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/testimonials?is_published=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/testimonials?is_published=0')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_list_returns_empty_state_cleanly(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/testimonials')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_can_view_a_testimonial(): void
    {
        $admin = User::factory()->admin()->create();
        $testimonial = Testimonial::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/testimonials/{$testimonial->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $testimonial->id);
    }

    public function test_admin_can_create_a_testimonial(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/testimonials', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('data.name.en', 'Ahmed Mohamed')
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.is_published', true);

        $testimonial = Testimonial::where('patient_name_en', 'Ahmed Mohamed')->firstOrFail();
        Storage::disk('public')->assertExists($testimonial->photo_path);
    }

    public function test_creating_a_testimonial_requires_a_photo(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->validPayload();
        unset($payload['photo']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/testimonials', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_photo_upload_rejects_non_image_files_and_oversized_files(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/testimonials', $this->validPayload([
                'photo' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/testimonials', $this->validPayload([
                'photo' => UploadedFile::fake()->image('big.jpg')->size(3000), // > 2MB
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_create_validates_required_fields_and_rating_range(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/testimonials', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'patient_name_ar', 'patient_name_en', 'role_ar', 'role_en', 'rating', 'quote_ar', 'quote_en', 'photo',
            ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/testimonials', $this->validPayload(['rating' => 6]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_admin_can_edit_a_testimonial_and_preserve_photo_when_none_uploaded(): void
    {
        $admin = User::factory()->admin()->create();
        $testimonial = Testimonial::factory()->create(['photo_path' => 'testimonials/original.jpg']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/testimonials/{$testimonial->id}", ['patient_name_en' => 'Updated Name'])
            ->assertOk()
            ->assertJsonPath('data.name.en', 'Updated Name');

        $this->assertSame('testimonials/original.jpg', $testimonial->fresh()->photo_path);
    }

    public function test_admin_can_replace_a_testimonials_photo_and_the_old_one_is_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('testimonials/old.jpg', 'fake-contents');
        $testimonial = Testimonial::factory()->create(['photo_path' => 'testimonials/old.jpg']);

        // Multipart update: POST + _method=PATCH (Laravel's spoofing convention),
        // since PHP never populates $_FILES for a raw PUT/PATCH body.
        $this->actingAs($admin, 'sanctum')
            ->post("/api/v1/admin/testimonials/{$testimonial->id}", [
                '_method' => 'PATCH',
                'photo' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertOk();

        $testimonial->refresh();
        Storage::disk('public')->assertExists($testimonial->photo_path);
        Storage::disk('public')->assertMissing('testimonials/old.jpg');
        $this->assertNotSame('testimonials/old.jpg', $testimonial->photo_path);
    }

    public function test_admin_can_publish_and_unpublish_a_testimonial(): void
    {
        $admin = User::factory()->admin()->create();
        $testimonial = Testimonial::factory()->create(['is_published' => true]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/testimonials/{$testimonial->id}", ['is_published' => false])
            ->assertOk()
            ->assertJsonPath('data.is_published', false);

        // Unpublishing drops it off the public site entirely.
        $this->getJson('/api/v1/testimonials')->assertJsonCount(0, 'data');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/testimonials/{$testimonial->id}", ['is_published' => true])
            ->assertOk()
            ->assertJsonPath('data.is_published', true);

        $this->getJson('/api/v1/testimonials')->assertJsonCount(1, 'data');
    }

    public function test_admin_can_delete_a_testimonial_and_the_photo_is_removed(): void
    {
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('testimonials/to-delete.jpg', 'fake-contents');
        $testimonial = Testimonial::factory()->create(['photo_path' => 'testimonials/to-delete.jpg']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/testimonials/{$testimonial->id}")
            ->assertOk();

        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
        Storage::disk('public')->assertMissing('testimonials/to-delete.jpg');
    }

    public function test_admin_can_update_bilingual_fields_independently(): void
    {
        $admin = User::factory()->admin()->create();
        $testimonial = Testimonial::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/testimonials/{$testimonial->id}", ['quote_ar' => 'اقتباس محدث'])
            ->assertOk()
            ->assertJsonPath('data.quote.ar', 'اقتباس محدث')
            ->assertJsonPath('data.quote.en', $testimonial->quote_en);
    }

    public function test_non_admins_cannot_manage_testimonials(): void
    {
        $patient = User::factory()->create();
        $doctorUser = User::factory()->doctor()->create();
        $testimonial = Testimonial::factory()->create();

        foreach ([$patient, $doctorUser] as $user) {
            $this->actingAs($user, 'sanctum')->getJson('/api/v1/admin/testimonials')->assertStatus(403);
            $this->actingAs($user, 'sanctum')->postJson('/api/v1/admin/testimonials', $this->validPayload())->assertStatus(403);
            $this->actingAs($user, 'sanctum')
                ->patchJson("/api/v1/admin/testimonials/{$testimonial->id}", ['patient_name_en' => 'Hacked'])
                ->assertStatus(403);
            $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/admin/testimonials/{$testimonial->id}")->assertStatus(403);
        }

        $this->assertDatabaseMissing('testimonials', ['patient_name_en' => 'Hacked']);
    }

    public function test_guests_cannot_access_admin_testimonial_endpoints(): void
    {
        $testimonial = Testimonial::factory()->create();

        $this->getJson('/api/v1/admin/testimonials')->assertStatus(401);
        $this->postJson('/api/v1/admin/testimonials', $this->validPayload())->assertStatus(401);
        $this->patchJson("/api/v1/admin/testimonials/{$testimonial->id}", ['patient_name_en' => 'X'])->assertStatus(401);
        $this->deleteJson("/api/v1/admin/testimonials/{$testimonial->id}")->assertStatus(401);
    }

    public function test_existing_public_testimonials_api_still_works(): void
    {
        Testimonial::factory()->create(['is_published' => true]);
        Testimonial::factory()->create(['is_published' => false]);

        $this->getJson('/api/v1/testimonials')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
