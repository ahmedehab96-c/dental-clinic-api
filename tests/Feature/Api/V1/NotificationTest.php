<?php

namespace Tests\Feature\Api\V1;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AppointmentCreatedNotification;
use App\Notifications\AppointmentStatusChangedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $patient;

    private User $doctorUser;

    private Doctor $doctor;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->patient = User::factory()->create();
        $this->doctorUser = User::factory()->doctor()->create();
        $this->doctor = Doctor::factory()->create(['user_id' => $this->doctorUser->id]);
        $this->service = Service::factory()->create(['name_ar' => 'تبييض', 'name_en' => 'Whitening']);
    }

    /** A fixed weekday (never Friday, the clinic's closed day) in the future. */
    private function bookableDate(): string
    {
        return Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
    }

    private function bookingPayload(array $overrides = []): array
    {
        return array_merge([
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'date' => $this->bookableDate(),
            'time' => '09:00',
            'patient_name' => 'Layla Test',
            'patient_phone' => '0551234567',
            'patient_email' => 'layla@example.com',
        ], $overrides);
    }

    private function appointment(array $attributes = []): Appointment
    {
        return Appointment::factory()->create(array_merge([
            'user_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'service_id' => $this->service->id,
            'status' => 'pending',
        ], $attributes));
    }

    private function notificationsOf(User $user, ?string $class = null)
    {
        return $user->notifications()->when($class, fn ($q) => $q->where('type', $class))->get();
    }

    private function asUser(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user, 'sanctum');
    }

    // --- Appointment integration -------------------------------------------

    public function test_a_patient_booking_notifies_the_patient_the_linked_doctor_and_admins_once_each(): void
    {
        $this->asUser($this->patient)->postJson('/api/v1/appointments', $this->bookingPayload())->assertCreated();

        foreach ([$this->patient, $this->doctorUser, $this->admin] as $user) {
            $this->assertCount(1, $this->notificationsOf($user, AppointmentCreatedNotification::class));
        }

        $data = $this->notificationsOf($this->patient)->first()->data;
        $this->assertSame('appointment_created', $data['kind']);
        $this->assertSame('pending', $data['status']);
        // Structured, bilingual data — not a stored English sentence.
        $this->assertSame(['ar' => 'تبييض', 'en' => 'Whitening'], $data['service']);
        $this->assertSame($this->doctor->name_ar, $data['doctor']['ar']);
        $this->assertSame($this->bookableDate(), $data['date']);
    }

    public function test_a_guest_booking_notifies_the_doctor_and_admins_only(): void
    {
        $this->postJson('/api/v1/appointments', $this->bookingPayload())->assertCreated();

        $this->assertCount(0, $this->notificationsOf($this->patient));
        $this->assertCount(1, $this->notificationsOf($this->doctorUser));
        $this->assertCount(1, $this->notificationsOf($this->admin));
    }

    public function test_failed_bookings_create_no_notifications(): void
    {
        $this->appointment(['date' => $this->bookableDate(), 'time' => '09:00']);

        // Slot already taken (the existing API answers 409 Conflict).
        $this->asUser($this->patient)->postJson('/api/v1/appointments', $this->bookingPayload())->assertStatus(409);
        // Validation failure.
        $this->asUser($this->patient)->postJson('/api/v1/appointments', ['service_id' => 999])->assertStatus(422);

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_doctor_confirming_notifies_the_patient_but_not_the_doctor_or_admins(): void
    {
        $appointment = $this->appointment();

        $this->asUser($this->doctorUser)
            ->patchJson("/api/v1/doctor/appointments/{$appointment->id}/status", ['status' => 'confirmed'])
            ->assertOk();

        $notification = $this->notificationsOf($this->patient, AppointmentStatusChangedNotification::class)->sole();
        $this->assertSame('confirmed', $notification->data['status']);
        $this->assertSame('pending', $notification->data['previous_status']);
        $this->assertSame('doctor', $notification->data['actor_role']);
        $this->assertCount(0, $this->notificationsOf($this->doctorUser));
        $this->assertCount(0, $this->notificationsOf($this->admin));
    }

    public function test_doctor_completing_notifies_the_patient(): void
    {
        $appointment = $this->appointment(['status' => 'confirmed']);

        $this->asUser($this->doctorUser)
            ->patchJson("/api/v1/doctor/appointments/{$appointment->id}/status", ['status' => 'completed'])
            ->assertOk();

        $this->assertSame('completed', $this->notificationsOf($this->patient)->sole()->data['status']);
    }

    public function test_patient_cancelling_notifies_the_doctor_and_admins_but_not_the_patient(): void
    {
        $appointment = $this->appointment(['status' => 'confirmed']);

        $this->asUser($this->patient)->postJson("/api/v1/me/appointments/{$appointment->id}/cancel")->assertOk();

        $this->assertCount(0, $this->notificationsOf($this->patient));
        $this->assertSame('cancelled', $this->notificationsOf($this->doctorUser)->sole()->data['status']);
        $this->assertSame('patient', $this->notificationsOf($this->admin)->sole()->data['actor_role']);
    }

    public function test_admin_status_change_notifies_patient_and_doctor_but_not_admins(): void
    {
        $otherAdmin = User::factory()->admin()->create();
        $appointment = $this->appointment();

        $this->asUser($this->admin)
            ->patchJson("/api/v1/admin/appointments/{$appointment->id}/status", ['status' => 'confirmed'])
            ->assertOk();

        $this->assertCount(1, $this->notificationsOf($this->patient));
        $this->assertCount(1, $this->notificationsOf($this->doctorUser));
        $this->assertCount(0, $this->notificationsOf($this->admin));
        $this->assertCount(0, $this->notificationsOf($otherAdmin));
    }

    public function test_repeating_the_same_status_creates_no_duplicate_notification(): void
    {
        $appointment = $this->appointment();

        for ($i = 0; $i < 2; $i++) {
            $this->asUser($this->admin)
                ->patchJson("/api/v1/admin/appointments/{$appointment->id}/status", ['status' => 'confirmed'])
                ->assertOk();
        }

        $this->assertCount(1, $this->notificationsOf($this->patient));
    }

    public function test_rejected_or_unauthorized_status_changes_create_no_notifications(): void
    {
        $appointment = $this->appointment();
        $otherDoctorUser = User::factory()->doctor()->create();
        Doctor::factory()->create(['user_id' => $otherDoctorUser->id]);

        // Invalid workflow step (pending → completed).
        $this->asUser($this->doctorUser)
            ->patchJson("/api/v1/doctor/appointments/{$appointment->id}/status", ['status' => 'completed'])
            ->assertStatus(422);
        // Invalid status value.
        $this->asUser($this->admin)
            ->patchJson("/api/v1/admin/appointments/{$appointment->id}/status", ['status' => 'bogus'])
            ->assertStatus(422);
        // Another doctor's appointment.
        $this->asUser($otherDoctorUser)
            ->patchJson("/api/v1/doctor/appointments/{$appointment->id}/status", ['status' => 'confirmed'])
            ->assertStatus(403);
        // Patient can't cancel an already-completed visit.
        $completed = $this->appointment(['status' => 'completed', 'date' => now()->addDays(9)]);
        $this->asUser($this->patient)->postJson("/api/v1/me/appointments/{$completed->id}/cancel")->assertStatus(403);

        $this->assertDatabaseCount('notifications', 0);
        $this->assertSame('pending', $appointment->fresh()->status);
    }

    // --- Notifications API -------------------------------------------------

    private int $seeded = 0;

    /**
     * Every seeded appointment gets its own day, so repeated calls in one
     * test never collide on the (doctor, date, time) unique index.
     */
    private function seedNotifications(User $user, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->seeded++;
            $user->notify(new AppointmentCreatedNotification(
                $this->appointment(['date' => now()->addDays($this->seeded)]),
            ));
        }
    }

    public function test_list_is_paginated_newest_first_and_exposes_only_public_fields(): void
    {
        $this->seedNotifications($this->patient, 3);

        $response = $this->asUser($this->patient)
            ->getJson('/api/v1/notifications?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.kind', 'appointment_created')
            ->assertJsonStructure(['data' => [['id', 'kind', 'data' => ['reference', 'service', 'date'], 'read_at', 'created_at']]]);

        $body = $response->getContent();
        $this->assertStringNotContainsString('notifiable', $body);
        $this->assertStringNotContainsString('App\\\\Notifications', $body);
    }

    public function test_unread_count_mark_one_and_mark_all_as_read(): void
    {
        $this->seedNotifications($this->patient, 3);

        $this->asUser($this->patient)->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.count', 3);

        $id = $this->patient->notifications()->first()->id;
        $this->asUser($this->patient)
            ->patchJson("/api/v1/notifications/{$id}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $id);
        $this->asUser($this->patient)->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.count', 2);
        $this->asUser($this->patient)->getJson('/api/v1/notifications?filter=unread')->assertJsonCount(2, 'data');

        $this->asUser($this->patient)->postJson('/api/v1/notifications/read-all')->assertOk()->assertJsonPath('data.updated', 2);
        $this->asUser($this->patient)->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.count', 0);
    }

    public function test_users_only_ever_see_and_modify_their_own_notifications(): void
    {
        $this->seedNotifications($this->patient, 2);
        $this->seedNotifications($this->doctorUser, 1);
        $patientNotificationId = $this->patient->notifications()->first()->id;

        $this->asUser($this->doctorUser)->getJson('/api/v1/notifications')->assertJsonCount(1, 'data');
        $this->asUser($this->doctorUser)->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.count', 1);

        // Another user's notification is simply not found for this account.
        $this->asUser($this->doctorUser)->patchJson("/api/v1/notifications/{$patientNotificationId}/read")->assertNotFound();
        // Read-all only touches the caller's own notifications.
        $this->asUser($this->doctorUser)->postJson('/api/v1/notifications/read-all')->assertJsonPath('data.updated', 1);

        $this->assertSame(2, $this->patient->unreadNotifications()->count());
    }

    public function test_guests_are_rejected(): void
    {
        $this->getJson('/api/v1/notifications')->assertStatus(401);
        $this->getJson('/api/v1/notifications/unread-count')->assertStatus(401);
        $this->patchJson('/api/v1/notifications/some-id/read')->assertStatus(401);
        $this->postJson('/api/v1/notifications/read-all')->assertStatus(401);
    }

    public function test_invalid_filter_is_rejected(): void
    {
        $this->asUser($this->patient)->getJson('/api/v1/notifications?filter=everything')->assertStatus(422);
    }
}
