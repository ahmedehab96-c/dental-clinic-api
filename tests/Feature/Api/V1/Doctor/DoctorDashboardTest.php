<?php

namespace Tests\Feature\Api\V1\Doctor;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DoctorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = Service::factory()->create();
    }

    /** @return array{0: User, 1: Doctor} */
    private function doctorAccount(array $doctorAttributes = []): array
    {
        $user = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create(['user_id' => $user->id] + $doctorAttributes);

        return [$user, $doctor];
    }

    private int $slot = 0;

    /**
     * Each call gets its own time slot — appointments are unique per
     * (doctor, date, time), so tests that share a date mustn't collide.
     */
    private function appointment(Doctor $doctor, array $attributes = []): Appointment
    {
        $this->slot++;

        return Appointment::factory()->create(array_merge([
            'doctor_id' => $doctor->id,
            'service_id' => $this->service->id,
            'status' => 'pending',
            'date' => today()->addDays(3),
            'time' => sprintf('%02d:00', 8 + $this->slot),
        ], $attributes));
    }

    public function test_dashboard_statistics_count_only_the_authenticated_doctors_appointments(): void
    {
        [$userA, $doctorA] = $this->doctorAccount();
        [, $doctorB] = $this->doctorAccount();
        $doctorA->services()->attach($this->service);

        $this->appointment($doctorA, ['date' => today(), 'status' => 'confirmed']);
        $this->appointment($doctorA, ['date' => today()->addDay(), 'status' => 'pending']);
        $this->appointment($doctorA, ['date' => today()->addDays(2), 'status' => 'cancelled']);
        $this->appointment($doctorA, ['date' => today()->subDay(), 'status' => 'completed']);
        // Doctor B's appointments must not leak into A's numbers.
        $this->appointment($doctorB, ['date' => today(), 'status' => 'pending']);
        $this->appointment($doctorB, ['date' => today()->addDay(), 'status' => 'pending']);

        $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v1/doctor/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_appointments', 4)
            ->assertJsonPath('data.today_appointments', 1)
            ->assertJsonPath('data.upcoming_appointments', 1) // the cancelled future one doesn't count
            ->assertJsonPath('data.pending_appointments', 1)
            ->assertJsonPath('data.confirmed_appointments', 1)
            ->assertJsonPath('data.completed_appointments', 1)
            ->assertJsonPath('data.cancelled_appointments', 1)
            ->assertJsonPath('data.services_count', 1);
    }

    public function test_dashboard_without_a_linked_profile_returns_a_clean_404(): void
    {
        $user = User::factory()->doctor()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/doctor/dashboard')->assertStatus(404);
    }

    public function test_appointments_can_be_scoped_to_today_upcoming_and_past(): void
    {
        [$user, $doctor] = $this->doctorAccount();
        $this->appointment($doctor, ['date' => today(), 'reference' => 'TODAY']);
        $this->appointment($doctor, ['date' => today()->addDays(5), 'reference' => 'LATER']);
        $this->appointment($doctor, ['date' => today()->addDay(), 'reference' => 'SOON']);
        $this->appointment($doctor, ['date' => today()->subDays(2), 'reference' => 'PAST']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/doctor/appointments?scope=today')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'TODAY');

        // Upcoming is soonest-first.
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/doctor/appointments?scope=upcoming')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.reference', 'SOON')
            ->assertJsonPath('data.1.reference', 'LATER');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/doctor/appointments?scope=past')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'PAST');
    }

    public function test_appointments_can_be_filtered_by_status_and_paginated(): void
    {
        [$user, $doctor] = $this->doctorAccount();
        $this->appointment($doctor, ['status' => 'pending']);
        $this->appointment($doctor, ['status' => 'confirmed']);
        $this->appointment($doctor, ['status' => 'confirmed']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/doctor/appointments?status=confirmed')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/doctor/appointments?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_invalid_scope_or_status_filters_are_rejected(): void
    {
        [$user] = $this->doctorAccount();

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/doctor/appointments?scope=forever')->assertStatus(422);
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/doctor/appointments?status=archived')->assertStatus(422);
    }

    public function test_a_client_supplied_doctor_id_cannot_widen_the_list(): void
    {
        [$userA, $doctorA] = $this->doctorAccount();
        [, $doctorB] = $this->doctorAccount();
        $this->appointment($doctorA);
        $this->appointment($doctorB);

        $this->actingAs($userA, 'sanctum')
            ->getJson("/api/v1/doctor/appointments?doctor_id={$doctorB->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.doctor.id', $doctorA->id);
    }

    public function test_doctor_can_view_their_own_appointment_details(): void
    {
        [$user, $doctor] = $this->doctorAccount();
        $appointment = $this->appointment($doctor, ['patient_name' => 'Sara Test']);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/doctor/appointments/{$appointment->id}")
            ->assertOk()
            ->assertJsonPath('data.patient.name', 'Sara Test');
    }

    public function test_doctor_a_cannot_view_or_modify_doctor_bs_appointment(): void
    {
        [$userA] = $this->doctorAccount();
        [, $doctorB] = $this->doctorAccount();
        $appointment = $this->appointment($doctorB);

        $this->actingAs($userA, 'sanctum')
            ->getJson("/api/v1/doctor/appointments/{$appointment->id}")
            ->assertStatus(403);

        $this->actingAs($userA, 'sanctum')
            ->patchJson("/api/v1/doctor/appointments/{$appointment->id}/status", ['status' => 'cancelled'])
            ->assertStatus(403);

        $this->assertSame('pending', $appointment->fresh()->status);
    }

    public function test_doctor_can_follow_the_status_workflow(): void
    {
        [$user, $doctor] = $this->doctorAccount();
        $appointment = $this->appointment($doctor);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/doctor/appointments/{$appointment->id}/status", ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/doctor/appointments/{$appointment->id}/status", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_doctor_cannot_make_disallowed_status_transitions(): void
    {
        [$user, $doctor] = $this->doctorAccount();
        $pending = $this->appointment($doctor, ['status' => 'pending']);
        $completed = $this->appointment($doctor, ['status' => 'completed']);
        $cancelled = $this->appointment($doctor, ['status' => 'cancelled']);

        // Skipping confirmation, and reopening terminal statuses, are refused.
        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/doctor/appointments/{$pending->id}/status", ['status' => 'completed'])
            ->assertStatus(422);
        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/doctor/appointments/{$completed->id}/status", ['status' => 'pending'])
            ->assertStatus(422);
        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/doctor/appointments/{$cancelled->id}/status", ['status' => 'confirmed'])
            ->assertStatus(422);

        // Unknown statuses fail validation outright.
        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/doctor/appointments/{$pending->id}/status", ['status' => 'no_show'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertSame('pending', $pending->fresh()->status);
        $this->assertSame('completed', $completed->fresh()->status);
        $this->assertSame('cancelled', $cancelled->fresh()->status);
    }

    public function test_admin_status_updates_are_unaffected_by_the_doctor_workflow_rules(): void
    {
        $admin = User::factory()->admin()->create();
        [, $doctor] = $this->doctorAccount();
        $appointment = $this->appointment($doctor, ['status' => 'completed']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/appointments/{$appointment->id}/status", ['status' => 'confirmed'])
            ->assertOk();
    }

    public function test_doctor_profile_exposes_their_own_record_with_services_and_no_secrets(): void
    {
        [$user, $doctor] = $this->doctorAccount(['email' => 'dr.a@clinic.test']);
        $doctor->services()->attach($this->service);

        $body = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/doctor/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $doctor->id)
            ->assertJsonPath('data.email', 'dr.a@clinic.test')
            ->assertJsonCount(1, 'data.services')
            ->getContent();

        $this->assertStringNotContainsString('password', $body);
        $this->assertStringNotContainsString('token', $body);
    }

    public function test_doctor_can_update_safe_profile_fields_and_photo(): void
    {
        [$user, $doctor] = $this->doctorAccount(['photo_path' => 'doctors/old.jpg']);
        Storage::disk('public')->put('doctors/old.jpg', 'x');

        $this->actingAs($user, 'sanctum')
            ->post('/api/v1/doctor/profile', [
                '_method' => 'PATCH',
                'name_en' => 'Dr. Updated',
                'bio_ar' => 'نبذة جديدة',
                'phone' => '0551234567',
                'photo' => UploadedFile::fake()->image('new.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.name.en', 'Dr. Updated')
            ->assertJsonPath('data.bio.ar', 'نبذة جديدة');

        $doctor->refresh();
        $this->assertSame('0551234567', $doctor->phone);
        Storage::disk('public')->assertExists($doctor->photo_path);
        Storage::disk('public')->assertMissing('doctors/old.jpg');
    }

    public function test_doctor_cannot_change_admin_managed_fields_services_or_ownership(): void
    {
        [$userA, $doctorA] = $this->doctorAccount(['is_active' => true, 'featured' => false]);
        [$userB, $doctorB] = $this->doctorAccount();
        $otherService = Service::factory()->create();
        $originalSlug = (string) $doctorA->slug;

        $this->actingAs($userA, 'sanctum')
            ->patchJson('/api/v1/doctor/profile', [
                'name_en' => 'Dr. Still A',
                'is_active' => false,
                'featured' => true,
                'slug' => 'hijacked',
                'rating' => 5,
                'user_id' => $userB->id,
                'service_ids' => [$otherService->id],
                'id' => $doctorB->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $doctorA->id);

        $doctorA->refresh();
        $this->assertSame('Dr. Still A', $doctorA->name_en);
        $this->assertTrue($doctorA->is_active);
        $this->assertFalse($doctorA->featured);
        $this->assertSame($originalSlug, $doctorA->slug);
        $this->assertSame($userA->id, $doctorA->user_id);
        $this->assertCount(0, $doctorA->services);
        $this->assertNotSame('Dr. Still A', $doctorB->fresh()->name_en);
    }

    public function test_profile_update_validates_fields(): void
    {
        [$user] = $this->doctorAccount();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/doctor/profile', ['name_en' => '', 'email' => 'nope', 'phone' => '123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name_en', 'email', 'phone']);

        $this->actingAs($user, 'sanctum')
            ->post('/api/v1/doctor/profile', [
                '_method' => 'PATCH',
                'photo' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_doctor_cannot_change_their_own_role(): void
    {
        [$user] = $this->doctorAccount();

        // The self-service profile endpoint ignores `role` entirely…
        $this->actingAs($user, 'sanctum')->patchJson('/api/v1/me', ['name' => 'Doc', 'role' => 'admin']);
        // …and the admin user endpoint is off-limits.
        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/admin/users/{$user->id}", ['role' => 'admin'])
            ->assertStatus(403);

        $this->assertSame('doctor', $user->fresh()->role);
    }

    public function test_patients_admins_and_guests_cannot_reach_doctor_endpoints(): void
    {
        [, $doctor] = $this->doctorAccount();
        $appointment = $this->appointment($doctor);
        $patient = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $endpoints = [
            ['getJson', '/api/v1/doctor/dashboard'],
            ['getJson', '/api/v1/doctor/profile'],
            ['patchJson', '/api/v1/doctor/profile'],
            ['getJson', '/api/v1/doctor/services'],
            ['getJson', '/api/v1/doctor/appointments'],
            ['getJson', "/api/v1/doctor/appointments/{$appointment->id}"],
            ['patchJson', "/api/v1/doctor/appointments/{$appointment->id}/status"],
        ];

        foreach ($endpoints as [$method, $url]) {
            $this->$method($url, ['status' => 'confirmed'])->assertStatus(401);

            foreach ([$patient, $admin] as $user) {
                $this->actingAs($user, 'sanctum')->$method($url, ['status' => 'confirmed'])->assertStatus(403);
            }
            $this->app['auth']->forgetGuards();
        }

        $this->assertSame('pending', $appointment->fresh()->status);
    }
}
