<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBlogManagementTest extends TestCase
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
            'slug' => 'oral-hygiene-tips',
            'category_id' => BlogCategory::factory()->create()->id,
            'title_ar' => 'نصائح لصحة الفم',
            'title_en' => 'Oral Hygiene Tips',
            'excerpt_ar' => 'مقتطف قصير',
            'excerpt_en' => 'Short excerpt',
            'content_ar' => ['فقرة أولى', 'فقرة ثانية'],
            'content_en' => ['First paragraph', 'Second paragraph'],
            'status' => 'draft',
            'image' => UploadedFile::fake()->image('post.jpg'),
        ], $overrides);
    }

    public function test_admin_can_list_posts_with_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        BlogPost::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/posts?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_list_includes_drafts(): void
    {
        $admin = User::factory()->admin()->create();
        BlogPost::factory()->create(['published_at' => null]);
        BlogPost::factory()->create(['published_at' => now()]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/posts')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_search_posts_by_title(): void
    {
        $admin = User::factory()->admin()->create();
        BlogPost::factory()->create(['title_en' => 'Dental Implants Explained']);
        BlogPost::factory()->create(['title_en' => 'Braces For Adults']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/posts?search=Implants')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title.en', 'Dental Implants Explained');
    }

    public function test_admin_can_filter_posts_by_category(): void
    {
        $admin = User::factory()->admin()->create();
        $categoryA = BlogCategory::factory()->create(['key' => 'general']);
        $categoryB = BlogCategory::factory()->create(['key' => 'cosmetic']);
        BlogPost::factory()->create(['category_id' => $categoryA->id]);
        BlogPost::factory()->create(['category_id' => $categoryB->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/posts?category=cosmetic')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category.key', 'cosmetic');
    }

    public function test_admin_can_filter_posts_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        BlogPost::factory()->create(['published_at' => null]);
        BlogPost::factory()->create(['published_at' => now()]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/posts?status=draft')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'draft');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/posts?status=published')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'published');
    }

    public function test_admin_list_returns_empty_state_cleanly(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/posts')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_can_view_a_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = BlogPost::factory()->create(['content_ar' => ['فقرة أولى', 'فقرة ثانية']]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('data.id', $post->id)
            ->assertJsonCount(2, 'data.content.ar');
    }

    public function test_admin_can_create_a_draft_post(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/posts', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'oral-hygiene-tips')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.published_at', null);

        $post = BlogPost::where('slug', 'oral-hygiene-tips')->firstOrFail();
        Storage::disk('public')->assertExists($post->image_path);
        $this->assertNull($post->published_at);
    }

    public function test_admin_can_create_a_published_post_defaulting_to_now(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/posts', $this->validPayload([
            'status' => 'published',
        ]));

        $response->assertCreated()->assertJsonPath('data.status', 'published');
        $post = BlogPost::where('slug', 'oral-hygiene-tips')->firstOrFail();
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->published_at->lessThanOrEqualTo(now()));
    }

    public function test_admin_can_schedule_a_published_post_for_a_future_date(): void
    {
        $admin = User::factory()->admin()->create();
        $future = now()->addDays(3)->startOfMinute();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/posts', $this->validPayload([
            'status' => 'published',
            'published_at' => $future->toIso8601String(),
        ]));

        $response->assertCreated();
        $post = BlogPost::where('slug', 'oral-hygiene-tips')->firstOrFail();
        $this->assertTrue($post->published_at->equalTo($future));

        // Scheduled for the future — not visible on the public site yet.
        $this->getJson('/api/v1/posts')->assertJsonCount(0, 'data');
    }

    public function test_creating_a_post_requires_an_image(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->validPayload();
        unset($payload['image']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/posts', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_image_upload_rejects_non_image_files_and_oversized_files(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/posts', $this->validPayload([
                'image' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/posts', $this->validPayload([
                'image' => UploadedFile::fake()->image('big.jpg')->size(3000), // > 2MB
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_create_validates_required_fields_and_unique_slug(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/posts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'slug', 'category_id', 'title_ar', 'title_en', 'excerpt_ar', 'excerpt_en',
                'content_ar', 'content_en', 'image',
            ]);

        BlogPost::factory()->create(['slug' => 'oral-hygiene-tips']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/posts', $this->validPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_admin_can_edit_a_post_and_preserve_image_when_none_uploaded(): void
    {
        $admin = User::factory()->admin()->create();
        $post = BlogPost::factory()->create(['image_path' => 'blog/original.jpg']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/posts/{$post->slug}", ['title_en' => 'Updated Title'])
            ->assertOk()
            ->assertJsonPath('data.title.en', 'Updated Title');

        $this->assertSame('blog/original.jpg', $post->fresh()->image_path);
    }

    public function test_admin_can_replace_a_posts_image_and_the_old_one_is_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('blog/old.jpg', 'fake-contents');
        $post = BlogPost::factory()->create(['image_path' => 'blog/old.jpg']);

        // Multipart update: POST + _method=PATCH (Laravel's spoofing convention),
        // since PHP never populates $_FILES for a raw PUT/PATCH body.
        $this->actingAs($admin, 'sanctum')
            ->post("/api/v1/admin/posts/{$post->slug}", [
                '_method' => 'PATCH',
                'image' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertOk();

        $post->refresh();
        Storage::disk('public')->assertExists($post->image_path);
        Storage::disk('public')->assertMissing('blog/old.jpg');
        $this->assertNotSame('blog/old.jpg', $post->image_path);
    }

    public function test_admin_can_publish_and_revert_a_post_to_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $post = BlogPost::factory()->create(['published_at' => null]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/posts/{$post->slug}", ['status' => 'published'])
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->getJson('/api/v1/posts')->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/posts/{$post->slug}", ['status' => 'draft'])
            ->assertOk()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.published_at', null);

        // Reverting to draft drops it off the public site entirely.
        $this->getJson('/api/v1/posts')->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/posts/{$post->slug}")->assertNotFound();
    }

    public function test_admin_can_update_bilingual_fields_independently(): void
    {
        $admin = User::factory()->admin()->create();
        $post = BlogPost::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/posts/{$post->slug}", [
                'title_ar' => 'عنوان محدث',
                'content_ar' => ['فقرة محدثة'],
            ])
            ->assertOk()
            ->assertJsonPath('data.title.ar', 'عنوان محدث')
            ->assertJsonPath('data.content.ar', ['فقرة محدثة'])
            ->assertJsonPath('data.title.en', $post->title_en);
    }

    public function test_admin_can_delete_a_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = BlogPost::factory()->create(['image_path' => 'blog/to-delete.jpg']);
        Storage::disk('public')->put('blog/to-delete.jpg', 'fake-contents');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/posts/{$post->slug}")
            ->assertOk();

        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
        Storage::disk('public')->assertMissing('blog/to-delete.jpg');
    }

    public function test_non_admins_cannot_manage_posts(): void
    {
        $patient = User::factory()->create();
        $doctorUser = User::factory()->doctor()->create();
        $post = BlogPost::factory()->create();

        foreach ([$patient, $doctorUser] as $user) {
            $this->actingAs($user, 'sanctum')->getJson('/api/v1/admin/posts')->assertStatus(403);
            $this->actingAs($user, 'sanctum')->postJson('/api/v1/admin/posts', $this->validPayload())->assertStatus(403);
            $this->actingAs($user, 'sanctum')
                ->patchJson("/api/v1/admin/posts/{$post->slug}", ['title_en' => 'Hacked'])
                ->assertStatus(403);
            $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/admin/posts/{$post->slug}")->assertStatus(403);
        }

        $this->assertDatabaseMissing('blog_posts', ['title_en' => 'Hacked']);
    }

    public function test_guests_cannot_access_admin_blog_endpoints(): void
    {
        $post = BlogPost::factory()->create();

        $this->getJson('/api/v1/admin/posts')->assertStatus(401);
        $this->postJson('/api/v1/admin/posts', $this->validPayload())->assertStatus(401);
        $this->patchJson("/api/v1/admin/posts/{$post->slug}", ['title_en' => 'X'])->assertStatus(401);
        $this->deleteJson("/api/v1/admin/posts/{$post->slug}")->assertStatus(401);
    }

    public function test_existing_public_blog_api_still_works(): void
    {
        BlogPost::factory()->create(['published_at' => now()->subDay()]);
        BlogPost::factory()->create(['published_at' => null]);
        BlogPost::factory()->create(['published_at' => now()->addDay()]);

        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $published = BlogPost::whereNotNull('published_at')->where('published_at', '<=', now())->firstOrFail();
        $this->getJson("/api/v1/posts/{$published->slug}")->assertOk();

        $draft = BlogPost::whereNull('published_at')->firstOrFail();
        $this->getJson("/api/v1/posts/{$draft->slug}")->assertNotFound();
    }
}
