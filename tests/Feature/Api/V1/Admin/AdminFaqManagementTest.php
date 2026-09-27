<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFaqManagementTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'question_ar' => 'كم تستغرق زيارة الفحص الدوري؟',
            'question_en' => 'How long does a routine checkup take?',
            'answer_ar' => 'عادةً ما تستغرق من ٣٠ إلى ٤٥ دقيقة.',
            'answer_en' => 'It usually takes 30 to 45 minutes.',
        ], $overrides);
    }

    public function test_admin_can_list_faqs_with_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        Faq::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/faqs?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_list_includes_unpublished_faqs(): void
    {
        $admin = User::factory()->admin()->create();
        Faq::factory()->create(['is_published' => false]);
        Faq::factory()->create(['is_published' => true]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/faqs')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_list_is_ordered_by_sort_order(): void
    {
        $admin = User::factory()->admin()->create();
        Faq::factory()->create(['question_en' => 'Third', 'sort_order' => 3]);
        Faq::factory()->create(['question_en' => 'First', 'sort_order' => 1]);
        Faq::factory()->create(['question_en' => 'Second', 'sort_order' => 2]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/faqs')
            ->assertOk()
            ->assertJsonPath('data.0.question.en', 'First')
            ->assertJsonPath('data.1.question.en', 'Second')
            ->assertJsonPath('data.2.question.en', 'Third');
    }

    public function test_admin_can_search_faqs_by_question_or_answer(): void
    {
        $admin = User::factory()->admin()->create();
        Faq::factory()->create(['question_en' => 'Do you accept insurance?']);
        Faq::factory()->create(['question_en' => 'What are your hours?']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/faqs?search=insurance')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.question.en', 'Do you accept insurance?');
    }

    public function test_admin_can_filter_faqs_by_published_status(): void
    {
        $admin = User::factory()->admin()->create();
        Faq::factory()->create(['is_published' => true]);
        Faq::factory()->create(['is_published' => false]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/faqs?is_published=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/faqs?is_published=0')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_list_returns_empty_state_cleanly(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/faqs')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_can_view_a_faq(): void
    {
        $admin = User::factory()->admin()->create();
        $faq = Faq::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/faqs/{$faq->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $faq->id);
    }

    public function test_admin_can_create_a_faq(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/faqs', $this->validPayload([
            'sort_order' => 5,
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.question.en', 'How long does a routine checkup take?')
            ->assertJsonPath('data.sort_order', 5)
            ->assertJsonPath('data.is_published', true);

        $this->assertDatabaseHas('faqs', ['question_en' => 'How long does a routine checkup take?']);
    }

    public function test_create_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/faqs', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['question_ar', 'question_en', 'answer_ar', 'answer_en']);
    }

    public function test_admin_can_edit_a_faq(): void
    {
        $admin = User::factory()->admin()->create();
        $faq = Faq::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/faqs/{$faq->id}", ['question_en' => 'Updated Question?'])
            ->assertOk()
            ->assertJsonPath('data.question.en', 'Updated Question?');

        $this->assertSame($faq->question_ar, $faq->fresh()->question_ar);
    }

    public function test_admin_can_update_the_sort_order(): void
    {
        $admin = User::factory()->admin()->create();
        $faq = Faq::factory()->create(['sort_order' => 1]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/faqs/{$faq->id}", ['sort_order' => 10])
            ->assertOk()
            ->assertJsonPath('data.sort_order', 10);
    }

    public function test_admin_can_publish_and_unpublish_a_faq(): void
    {
        $admin = User::factory()->admin()->create();
        $faq = Faq::factory()->create(['is_published' => true]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/faqs/{$faq->id}", ['is_published' => false])
            ->assertOk()
            ->assertJsonPath('data.is_published', false);

        // Unpublishing drops it off the public site entirely.
        $this->getJson('/api/v1/faqs')->assertJsonCount(0, 'data');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/faqs/{$faq->id}", ['is_published' => true])
            ->assertOk()
            ->assertJsonPath('data.is_published', true);

        $this->getJson('/api/v1/faqs')->assertJsonCount(1, 'data');
    }

    public function test_admin_can_delete_a_faq(): void
    {
        $admin = User::factory()->admin()->create();
        $faq = Faq::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/faqs/{$faq->id}")
            ->assertOk();

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_admin_can_update_bilingual_fields_independently(): void
    {
        $admin = User::factory()->admin()->create();
        $faq = Faq::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/faqs/{$faq->id}", ['answer_ar' => 'إجابة محدثة'])
            ->assertOk()
            ->assertJsonPath('data.answer.ar', 'إجابة محدثة')
            ->assertJsonPath('data.answer.en', $faq->answer_en);
    }

    public function test_non_admins_cannot_manage_faqs(): void
    {
        $patient = User::factory()->create();
        $doctorUser = User::factory()->doctor()->create();
        $faq = Faq::factory()->create();

        foreach ([$patient, $doctorUser] as $user) {
            $this->actingAs($user, 'sanctum')->getJson('/api/v1/admin/faqs')->assertStatus(403);
            $this->actingAs($user, 'sanctum')->postJson('/api/v1/admin/faqs', $this->validPayload())->assertStatus(403);
            $this->actingAs($user, 'sanctum')
                ->patchJson("/api/v1/admin/faqs/{$faq->id}", ['question_en' => 'Hacked?'])
                ->assertStatus(403);
            $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/admin/faqs/{$faq->id}")->assertStatus(403);
        }

        $this->assertDatabaseMissing('faqs', ['question_en' => 'Hacked?']);
    }

    public function test_guests_cannot_access_admin_faq_endpoints(): void
    {
        $faq = Faq::factory()->create();

        $this->getJson('/api/v1/admin/faqs')->assertStatus(401);
        $this->postJson('/api/v1/admin/faqs', $this->validPayload())->assertStatus(401);
        $this->patchJson("/api/v1/admin/faqs/{$faq->id}", ['question_en' => 'X?'])->assertStatus(401);
        $this->deleteJson("/api/v1/admin/faqs/{$faq->id}")->assertStatus(401);
    }

    public function test_existing_public_faq_api_still_works(): void
    {
        Faq::factory()->create(['is_published' => true, 'sort_order' => 2, 'question_en' => 'Second']);
        Faq::factory()->create(['is_published' => true, 'sort_order' => 1, 'question_en' => 'First']);
        Faq::factory()->create(['is_published' => false, 'question_en' => 'Hidden']);

        $this->getJson('/api/v1/faqs')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.question.en', 'First')
            ->assertJsonPath('data.1.question.en', 'Second');
    }
}
