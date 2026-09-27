<?php

namespace Tests\Feature\Api\V1;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_services_with_bilingual_fields(): void
    {
        Service::factory()->create([
            'name_ar' => 'الفحص الدوري',
            'name_en' => 'General Checkup',
        ]);

        $response = $this->getJson('/api/v1/services');

        $response->assertOk()
            ->assertJsonPath('data.0.name.ar', 'الفحص الدوري')
            ->assertJsonPath('data.0.name.en', 'General Checkup');
    }

    public function test_show_returns_service_by_slug_with_features(): void
    {
        Service::factory()->create([
            'slug' => 'teeth-whitening',
            'features' => [['ar' => 'أ', 'en' => 'A']],
        ]);

        $this->getJson('/api/v1/services/teeth-whitening')
            ->assertOk()
            ->assertJsonPath('data.slug', 'teeth-whitening')
            ->assertJsonPath('data.features.0.en', 'A');
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/v1/services/does-not-exist')->assertNotFound();
    }
}
