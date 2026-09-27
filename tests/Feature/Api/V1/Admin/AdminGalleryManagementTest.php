<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\GalleryCase;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminGalleryManagementTest extends TestCase
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
            'title_ar' => 'حالة تبييض الأسنان',
            'title_en' => 'Teeth Whitening Case',
            'service_id' => Service::factory()->create()->id,
            'before_image' => UploadedFile::fake()->image('before.jpg'),
            'after_image' => UploadedFile::fake()->image('after.jpg'),
        ], $overrides);
    }

    public function test_admin_can_list_gallery_cases_with_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        GalleryCase::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/gallery?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_list_includes_unpublished_cases(): void
    {
        $admin = User::factory()->admin()->create();
        GalleryCase::factory()->create(['is_published' => false]);
        GalleryCase::factory()->create(['is_published' => true]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/gallery')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_search_gallery_cases_by_title(): void
    {
        $admin = User::factory()->admin()->create();
        GalleryCase::factory()->create(['title_en' => 'Dental Implant Transformation']);
        GalleryCase::factory()->create(['title_en' => 'Braces Before And After']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/gallery?search=Implant')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title.en', 'Dental Implant Transformation');
    }

    public function test_admin_can_filter_gallery_cases_by_service(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceA = Service::factory()->create(['slug' => 'whitening']);
        $serviceB = Service::factory()->create(['slug' => 'braces']);
        GalleryCase::factory()->create(['service_id' => $serviceA->id]);
        GalleryCase::factory()->create(['service_id' => $serviceB->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/gallery?service=braces')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.service.slug', 'braces');
    }

    public function test_admin_can_filter_gallery_cases_by_published_status(): void
    {
        $admin = User::factory()->admin()->create();
        GalleryCase::factory()->create(['is_published' => true]);
        GalleryCase::factory()->create(['is_published' => false]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/gallery?is_published=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/gallery?is_published=0')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_list_returns_empty_state_cleanly(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/gallery')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_can_view_a_gallery_case(): void
    {
        $admin = User::factory()->admin()->create();
        $case = GalleryCase::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/gallery/{$case->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $case->id)
            ->assertJsonPath('data.service.id', $case->service_id);
    }

    public function test_admin_can_create_a_gallery_case(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/gallery', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('data.title.en', 'Teeth Whitening Case')
            ->assertJsonPath('data.is_published', true);

        $case = GalleryCase::where('title_en', 'Teeth Whitening Case')->firstOrFail();
        Storage::disk('public')->assertExists($case->before_image_path);
        Storage::disk('public')->assertExists($case->after_image_path);
    }

    public function test_creating_a_gallery_case_requires_both_images(): void
    {
        $admin = User::factory()->admin()->create();

        $payload = $this->validPayload();
        unset($payload['before_image']);
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/gallery', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['before_image']);

        $payload = $this->validPayload();
        unset($payload['after_image']);
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/gallery', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['after_image']);
    }

    public function test_image_upload_rejects_non_image_files_and_oversized_files(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/gallery', $this->validPayload([
                'before_image' => UploadedFile::fake()->create('scan.pdf', 100, 'application/pdf'),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['before_image']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/gallery', $this->validPayload([
                'after_image' => UploadedFile::fake()->image('big.jpg')->size(3000), // > 2MB
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['after_image']);
    }

    public function test_create_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/gallery', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title_ar', 'title_en', 'service_id', 'before_image', 'after_image']);
    }

    public function test_admin_can_edit_a_gallery_case_and_preserve_images_when_none_uploaded(): void
    {
        $admin = User::factory()->admin()->create();
        $case = GalleryCase::factory()->create([
            'before_image_path' => 'gallery/before-original.jpg',
            'after_image_path' => 'gallery/after-original.jpg',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/gallery/{$case->id}", ['title_en' => 'Updated Title'])
            ->assertOk()
            ->assertJsonPath('data.title.en', 'Updated Title');

        $fresh = $case->fresh();
        $this->assertSame('gallery/before-original.jpg', $fresh->before_image_path);
        $this->assertSame('gallery/after-original.jpg', $fresh->after_image_path);
    }

    public function test_admin_can_replace_only_the_before_image_independently(): void
    {
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('gallery/before-old.jpg', 'fake-contents');
        Storage::disk('public')->put('gallery/after-old.jpg', 'fake-contents');
        $case = GalleryCase::factory()->create([
            'before_image_path' => 'gallery/before-old.jpg',
            'after_image_path' => 'gallery/after-old.jpg',
        ]);

        // Multipart update: POST + _method=PATCH (Laravel's spoofing convention),
        // since PHP never populates $_FILES for a raw PUT/PATCH body.
        $this->actingAs($admin, 'sanctum')
            ->post("/api/v1/admin/gallery/{$case->id}", [
                '_method' => 'PATCH',
                'before_image' => UploadedFile::fake()->image('before-new.jpg'),
            ])
            ->assertOk();

        $fresh = $case->fresh();
        Storage::disk('public')->assertExists($fresh->before_image_path);
        Storage::disk('public')->assertMissing('gallery/before-old.jpg');
        $this->assertNotSame('gallery/before-old.jpg', $fresh->before_image_path);
        // The untouched after-image is left exactly as it was.
        $this->assertSame('gallery/after-old.jpg', $fresh->after_image_path);
        Storage::disk('public')->assertExists('gallery/after-old.jpg');
    }

    public function test_admin_can_publish_and_unpublish_a_gallery_case(): void
    {
        $admin = User::factory()->admin()->create();
        $case = GalleryCase::factory()->create(['is_published' => true]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/gallery/{$case->id}", ['is_published' => false])
            ->assertOk()
            ->assertJsonPath('data.is_published', false);

        // Unpublishing drops it off the public site entirely.
        $this->getJson('/api/v1/gallery')->assertJsonCount(0, 'data');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/gallery/{$case->id}", ['is_published' => true])
            ->assertOk()
            ->assertJsonPath('data.is_published', true);

        $this->getJson('/api/v1/gallery')->assertJsonCount(1, 'data');
    }

    public function test_admin_can_delete_a_gallery_case_and_both_images_are_removed(): void
    {
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('gallery/before-delete.jpg', 'fake-contents');
        Storage::disk('public')->put('gallery/after-delete.jpg', 'fake-contents');
        $case = GalleryCase::factory()->create([
            'before_image_path' => 'gallery/before-delete.jpg',
            'after_image_path' => 'gallery/after-delete.jpg',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/gallery/{$case->id}")
            ->assertOk();

        $this->assertDatabaseMissing('gallery_cases', ['id' => $case->id]);
        Storage::disk('public')->assertMissing('gallery/before-delete.jpg');
        Storage::disk('public')->assertMissing('gallery/after-delete.jpg');
    }

    public function test_admin_can_update_bilingual_titles_independently(): void
    {
        $admin = User::factory()->admin()->create();
        $case = GalleryCase::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/gallery/{$case->id}", ['title_ar' => 'عنوان محدث'])
            ->assertOk()
            ->assertJsonPath('data.title.ar', 'عنوان محدث')
            ->assertJsonPath('data.title.en', $case->title_en);
    }

    public function test_non_admins_cannot_manage_gallery(): void
    {
        $patient = User::factory()->create();
        $doctorUser = User::factory()->doctor()->create();
        $case = GalleryCase::factory()->create();

        foreach ([$patient, $doctorUser] as $user) {
            $this->actingAs($user, 'sanctum')->getJson('/api/v1/admin/gallery')->assertStatus(403);
            $this->actingAs($user, 'sanctum')->postJson('/api/v1/admin/gallery', $this->validPayload())->assertStatus(403);
            $this->actingAs($user, 'sanctum')
                ->patchJson("/api/v1/admin/gallery/{$case->id}", ['title_en' => 'Hacked'])
                ->assertStatus(403);
            $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/admin/gallery/{$case->id}")->assertStatus(403);
        }

        $this->assertDatabaseMissing('gallery_cases', ['title_en' => 'Hacked']);
    }

    public function test_guests_cannot_access_admin_gallery_endpoints(): void
    {
        $case = GalleryCase::factory()->create();

        $this->getJson('/api/v1/admin/gallery')->assertStatus(401);
        $this->postJson('/api/v1/admin/gallery', $this->validPayload())->assertStatus(401);
        $this->patchJson("/api/v1/admin/gallery/{$case->id}", ['title_en' => 'X'])->assertStatus(401);
        $this->deleteJson("/api/v1/admin/gallery/{$case->id}")->assertStatus(401);
    }

    public function test_existing_public_gallery_api_still_works(): void
    {
        $service = Service::factory()->create(['slug' => 'whitening']);
        GalleryCase::factory()->create(['is_published' => true, 'service_id' => $service->id]);
        GalleryCase::factory()->create(['is_published' => false]);

        $this->getJson('/api/v1/gallery')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'whitening');

        $this->getJson('/api/v1/gallery?service=whitening')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
