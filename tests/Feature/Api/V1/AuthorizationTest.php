<?php

namespace Tests\Feature\Api\V1;

use App\Models\ClinicSetting;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Cross-cutting checks that don't belong to one role's own test file:
 * public endpoints stay open, and the 401 (no identity) vs 403 (wrong
 * role) distinction holds across every gated route group.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_read_endpoints_remain_open_without_authentication(): void
    {
        Doctor::factory()->create();
        Service::factory()->create();
        ClinicSetting::create([
            'address_ar' => 'عنوان', 'address_en' => 'Address',
            'phone' => '0500000000', 'whatsapp' => '0500000000', 'email' => 'clinic@example.com',
            'working_hours_ar' => '9-5', 'working_hours_en' => '9-5',
        ]);

        $this->getJson('/api/v1/doctors')->assertOk();
        $this->getJson('/api/v1/services')->assertOk();
        $this->getJson('/api/v1/testimonials')->assertOk();
        $this->getJson('/api/v1/faqs')->assertOk();
        $this->getJson('/api/v1/clinic-settings')->assertOk();
    }

    public function test_guest_booking_endpoint_remains_open_without_authentication(): void
    {
        $service = Service::factory()->create();

        $this->postJson('/api/v1/appointments', [
            'service_id' => $service->id,
            // Carbon::addWeekday() only skips Sat/Sun — the clinic is also
            // closed Fridays, so pick a fixed weekday that's never Friday.
            'date' => Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d'),
            'time' => '10:00',
            'patient_name' => 'Guest Patient',
            'patient_phone' => '0551234567',
            'patient_email' => 'guest@example.com',
        ])->assertCreated();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function guardedEndpoints(): array
    {
        return [
            'admin appointments' => ['GET', '/api/v1/admin/appointments'],
            'admin users' => ['GET', '/api/v1/admin/users'],
            'doctor profile' => ['GET', '/api/v1/doctor/profile'],
            'own profile' => ['GET', '/api/v1/me'],
            'own appointments' => ['GET', '/api/v1/me/appointments'],
        ];
    }

    #[DataProvider('guardedEndpoints')]
    public function test_guarded_endpoint_rejects_unauthenticated_requests(string $method, string $uri): void
    {
        $this->json($method, $uri)
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_role_mismatch_returns_403_not_401_when_authenticated(): void
    {
        $patient = User::factory()->create();

        // Authenticated (has a valid identity) but the wrong role for this
        // route group — must be 403, never 401.
        $this->actingAs($patient, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_invalid_token_is_rejected_as_unauthenticated(): void
    {
        $this->withHeader('Authorization', 'Bearer not-a-real-token')
            ->getJson('/api/v1/me')
            ->assertStatus(401);
    }
}
