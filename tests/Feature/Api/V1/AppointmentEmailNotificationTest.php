<?php

namespace Tests\Feature\Api\V1;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AppointmentCreatedNotification;
use App\Notifications\AppointmentStatusChangedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class AppointmentEmailNotificationTest extends TestCase
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

        $this->admin = User::factory()->admin()->create(['email' => 'admin@clinic.test']);
        $this->patient = User::factory()->create(['name' => 'Layla Patient', 'email' => 'layla@patient.test']);
        $this->doctorUser = User::factory()->doctor()->create(['name' => 'Dr. Sara', 'email' => 'sara@doctor.test']);
        $this->doctor = Doctor::factory()->create(['user_id' => $this->doctorUser->id, 'name_en' => 'Dr. Sara Alamri', 'name_ar' => 'د. سارة العمري']);
        $this->service = Service::factory()->create(['name_ar' => 'تبييض الأسنان', 'name_en' => 'Teeth Whitening']);
        ClinicSetting::query()->create([
            'phone' => '+966550000000', 'whatsapp' => '966550000000', 'email' => 'hello@clinic.test',
            'address_ar' => 'الرياض', 'address_en' => 'Riyadh',
            'working_hours_ar' => 'السبت - الخميس', 'working_hours_en' => 'Sat - Thu',
        ]);
    }

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
            'patient_name' => 'Layla Patient',
            'patient_phone' => '0551234567',
            'patient_email' => 'layla.booking@patient.test',
        ], $overrides);
    }

    private function appointment(array $attributes = []): Appointment
    {
        return Appointment::factory()->create(array_merge([
            'user_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'service_id' => $this->service->id,
            'status' => 'pending',
            'date' => $this->bookableDate(),
            'time' => '10:00',
            'patient_name' => 'Layla Patient',
        ], $attributes));
    }

    private function asUser(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user, 'sanctum');
    }

    /** @return array<int, Email> */
    private function sentEmails(): array
    {
        return collect(app('mailer')->getSymfonyTransport()->messages())
            ->map(fn ($sent) => $sent->getOriginalMessage())
            ->all();
    }

    private function emailTo(string $address): ?Email
    {
        return collect($this->sentEmails())->first(
            fn (Email $email) => collect($email->getTo())->contains(fn ($to) => $to->getAddress() === $address),
        );
    }

    // --- Channels & recipients ---------------------------------------------

    public function test_booking_uses_mail_for_patient_and_doctor_but_in_app_only_for_admins(): void
    {
        Notification::fake();

        $this->asUser($this->patient)->postJson('/api/v1/appointments', $this->bookingPayload())->assertCreated();

        foreach ([$this->patient, $this->doctorUser] as $user) {
            Notification::assertSentTo($user, AppointmentCreatedNotification::class,
                fn ($notification, array $channels) => $channels === ['database', 'mail']);
        }
        Notification::assertSentTo($this->admin, AppointmentCreatedNotification::class,
            fn ($notification, array $channels) => $channels === ['database']);
        // A logged-in patient is reached through their account, not a second on-demand email.
        Notification::assertSentOnDemandTimes(AppointmentCreatedNotification::class, 0);
    }

    public function test_a_guest_booking_emails_the_address_they_booked_with(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/appointments', $this->bookingPayload())->assertCreated();

        Notification::assertSentOnDemand(AppointmentCreatedNotification::class,
            fn ($notification, array $channels, AnonymousNotifiable $notifiable) => $channels === ['mail']
                && $notifiable->routes['mail'] === 'layla.booking@patient.test');
        Notification::assertNotSentTo($this->patient, AppointmentCreatedNotification::class);
    }

    public function test_doctor_confirmation_emails_only_the_patient(): void
    {
        Notification::fake();
        $appointment = $this->appointment();

        $this->asUser($this->doctorUser)
            ->patchJson("/api/v1/doctor/appointments/{$appointment->id}/status", ['status' => 'confirmed'])
            ->assertOk();

        Notification::assertSentTo($this->patient, AppointmentStatusChangedNotification::class,
            fn ($notification, array $channels) => in_array('mail', $channels, true));
        Notification::assertNotSentTo($this->doctorUser, AppointmentStatusChangedNotification::class);
        Notification::assertNotSentTo($this->admin, AppointmentStatusChangedNotification::class);
    }

    public function test_guest_appointments_get_status_emails_on_demand(): void
    {
        Notification::fake();
        $appointment = $this->appointment(['user_id' => null, 'patient_email' => 'guest@patient.test']);

        $this->asUser($this->admin)
            ->patchJson("/api/v1/admin/appointments/{$appointment->id}/status", ['status' => 'confirmed'])
            ->assertOk();

        Notification::assertSentOnDemand(AppointmentStatusChangedNotification::class,
            fn ($notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'guest@patient.test');
    }

    public function test_repeated_or_rejected_status_changes_send_no_extra_email(): void
    {
        Notification::fake();
        $appointment = $this->appointment();
        $otherDoctor = User::factory()->doctor()->create();
        Doctor::factory()->create(['user_id' => $otherDoctor->id]);

        for ($i = 0; $i < 3; $i++) {
            $this->asUser($this->admin)
                ->patchJson("/api/v1/admin/appointments/{$appointment->id}/status", ['status' => 'confirmed'])
                ->assertOk();
        }
        $this->asUser($otherDoctor)
            ->patchJson("/api/v1/doctor/appointments/{$appointment->id}/status", ['status' => 'cancelled'])
            ->assertStatus(403);
        $this->asUser($this->doctorUser)
            ->patchJson("/api/v1/doctor/appointments/{$appointment->id}/status", ['status' => 'pending'])
            ->assertStatus(422);

        Notification::assertSentToTimes($this->patient, AppointmentStatusChangedNotification::class, 1);
        Notification::assertSentToTimes($this->doctorUser, AppointmentStatusChangedNotification::class, 1);
        Notification::assertNotSentTo($otherDoctor, AppointmentStatusChangedNotification::class);
    }

    // --- Real delivery through the database queue --------------------------

    public function test_email_is_queued_while_in_app_notifications_are_written_immediately(): void
    {
        config(['queue.default' => 'database']);

        $this->asUser($this->patient)->postJson('/api/v1/appointments', $this->bookingPayload())->assertCreated();

        // In-app: already there for patient, doctor and admin.
        $this->assertDatabaseCount('notifications', 3);
        // Email: one queued job each for the patient and the doctor, nothing sent yet.
        $this->assertSame(2, DB::table('jobs')->count());
        $this->assertCount(0, $this->sentEmails());

        $this->artisan('queue:work', ['--stop-when-empty' => true, '--sleep' => 0])->assertExitCode(0);

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertCount(2, $this->sentEmails());
        $this->assertNull($this->emailTo('admin@clinic.test'));

        $patientEmail = $this->emailTo('layla@patient.test');
        $this->assertStringStartsWith('We received your appointment request (APT-', $patientEmail->getSubject());
        $html = $patientEmail->getHtmlBody();
        $this->assertStringContainsString('dir="ltr"', $html);
        $this->assertStringContainsString('Teeth Whitening', $html);
        $this->assertStringContainsString('Dr. Sara Alamri', $html);
        $this->assertStringContainsString('09:00', $html);
        $this->assertStringContainsString('Pending', $html);
        $this->assertStringContainsString('hello@clinic.test', $html);
        $this->assertStringContainsString('/appointments', $html);

        $doctorEmail = $this->emailTo('sara@doctor.test');
        $this->assertStringStartsWith('New appointment assigned to you (APT-', $doctorEmail->getSubject());
        $this->assertStringContainsString('Layla Patient', $doctorEmail->getHtmlBody());
        // The doctor's copy never includes the patient's contact details.
        $this->assertStringNotContainsString('0551234567', $doctorEmail->getHtmlBody());
        $this->assertStringNotContainsString('layla.booking@patient.test', $doctorEmail->getHtmlBody());
    }

    public function test_status_change_is_queued_and_the_request_does_not_wait_for_it(): void
    {
        config(['queue.default' => 'database']);
        $appointment = $this->appointment(['status' => 'confirmed']);

        $this->asUser($this->patient)->postJson("/api/v1/me/appointments/{$appointment->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertCount(0, $this->sentEmails());
        $this->assertSame(1, DB::table('jobs')->count()); // doctor only — the patient made the change

        $this->artisan('queue:work', ['--stop-when-empty' => true, '--sleep' => 0]);

        $this->assertSame(
            'Appointment cancelled by the patient ('.$appointment->reference.')',
            $this->emailTo('sara@doctor.test')->getSubject(),
        );
    }

    public function test_arabic_email_is_rendered_right_to_left(): void
    {
        $appointment = $this->appointment(['status' => 'confirmed']);

        $this->patient->notify((new AppointmentStatusChangedNotification($appointment, 'pending', 'doctor'))->locale('ar'));

        $email = $this->emailTo('layla@patient.test');
        $this->assertSame("تم تأكيد موعدك ({$appointment->reference})", $email->getSubject());
        $html = $email->getHtmlBody();
        $this->assertStringContainsString('lang="ar" dir="rtl"', $html);
        $this->assertStringContainsString('تبييض الأسنان', $html);
        $this->assertStringContainsString('د. سارة العمري', $html);
        $this->assertStringContainsString('مؤكد', $html);
        $this->assertStringContainsString('الرياض', $html);
    }

    // --- Failure handling --------------------------------------------------

    public function test_a_broken_mail_server_never_breaks_booking_or_in_app_notifications(): void
    {
        // Sync queue: the mail send happens inside the request and fails.
        config([
            'mail.default' => 'broken',
            'mail.mailers.broken' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'timeout' => 1],
        ]);

        $this->asUser($this->patient)->postJson('/api/v1/appointments', $this->bookingPayload())
            ->assertCreated();

        $this->assertDatabaseCount('appointments', 1);
        $this->assertSame(1, $this->patient->notifications()->count());
    }

    public function test_queued_email_failures_retry_then_land_in_failed_jobs(): void
    {
        config([
            'queue.default' => 'database',
            'mail.default' => 'broken',
            'mail.mailers.broken' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'timeout' => 1],
        ]);
        $appointment = $this->appointment();

        $this->asUser($this->doctorUser)
            ->patchJson("/api/v1/doctor/appointments/{$appointment->id}/status", ['status' => 'confirmed'])
            ->assertOk();

        // Three attempts ($tries), skipping the backoff delay between them.
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            DB::table('jobs')->update(['available_at' => now()->getTimestamp()]);
            $this->artisan('queue:work', ['--stop-when-empty' => true, '--sleep' => 0]);
        }

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(1, DB::table('failed_jobs')->count());
        // The business change and the in-app notification are untouched.
        $this->assertSame('confirmed', $appointment->fresh()->status);
        $this->assertSame(1, $this->patient->notifications()->count());
        $this->assertDatabaseCount('appointments', 1);
    }
}
