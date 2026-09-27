<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Shared shape for appointment notifications.
 *
 * - `database` (in-app): stored immediately on the `sync` connection, so the
 *   unread count is correct the moment the request finishes — unchanged
 *   from before.
 * - `mail`: pushed onto the app's default queue connection, so a booking or
 *   status change never waits on SMTP. Failures retry via $tries/backoff()
 *   and then land in `failed_jobs`.
 *
 * The payload is snapshotted when the action happens, so a queued email
 * describes the appointment as it was at that moment even if it changes
 * again before the worker runs. Nothing is stored as English-only text:
 * in-app data is structured, and email copy is rendered per locale.
 */
abstract class AppointmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Queued email attempts before the job is recorded in failed_jobs. */
    public int $tries = 3;

    /** @var array<string, mixed> */
    protected array $snapshot;

    public function __construct(public readonly Appointment $appointment)
    {
        $this->snapshot = $this->buildPayload();
    }

    abstract protected function kind(): string;

    /** Translation key (under appointment_mail.) for this recipient's subject/intro. */
    abstract protected function mailCopyKey(string $audience): string;

    /**
     * Admins keep in-app only (no email fan-out to every admin account);
     * patients and doctors get both; a guest booking — an on-demand mail
     * route to the address they booked with — gets email only.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        if ($notifiable instanceof User && $notifiable->isAdmin()) {
            return ['database'];
        }

        return ['database', 'mail'];
    }

    /**
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $audience = $this->audienceFor($notifiable);
        $data = $this->snapshot;
        $locale = app()->getLocale();
        $copyKey = 'appointment_mail.'.$this->mailCopyKey($audience);
        $status = __('appointment_mail.statuses.'.$data['status']);
        $replace = ['reference' => $data['reference'], 'status' => $status];
        $subject = __($copyKey.'.subject', $replace);

        $details = [__('appointment_mail.labels.reference') => $data['reference']];
        if ($audience === 'doctor') {
            // A doctor only needs to know who is coming — no contact details.
            $details[__('appointment_mail.labels.patient')] = $data['patient_name'];
        } else {
            $details[__('appointment_mail.labels.doctor')] = $this->localized($data['doctor'], $locale) ?? __('appointment_mail.labels.any_doctor');
        }
        $details[__('appointment_mail.labels.service')] = $this->localized($data['service'], $locale) ?? '—';
        $details[__('appointment_mail.labels.date')] = Carbon::parse($data['date'])->locale($locale)->translatedFormat('l j F Y');
        $details[__('appointment_mail.labels.time')] = $data['time'];
        $details[__('appointment_mail.labels.status')] = $status;

        $clinic = ClinicSetting::query()->first();
        $name = $notifiable instanceof User ? $notifiable->name : $data['patient_name'];

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.appointment', [
                'subject' => $subject,
                'greeting' => $name ? __('appointment_mail.greeting', ['name' => $name]) : __('appointment_mail.greeting_guest'),
                'intro' => __($copyKey.'.intro', $replace),
                'details' => $details,
                'actionUrl' => $this->actionUrlFor($notifiable),
                'actionText' => __('appointment_mail.cta.'.($audience === 'doctor' ? 'doctor' : 'patient')),
                'clinic' => [
                    'phone' => $clinic?->phone,
                    'email' => $clinic?->email,
                    'address' => $clinic?->{'address_'.($locale === 'ar' ? 'ar' : 'en')},
                ],
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return $this->snapshot;
    }

    protected function audienceFor(object $notifiable): string
    {
        return $notifiable instanceof User && $notifiable->isDoctor() ? 'doctor' : 'patient';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(): array
    {
        $appointment = $this->appointment->loadMissing(['service', 'doctor']);

        return [
            'kind' => $this->kind(),
            'appointment_id' => $appointment->id,
            'reference' => $appointment->reference,
            'status' => $appointment->status,
            'date' => $appointment->date->format('Y-m-d'),
            'time' => substr((string) $appointment->time, 0, 5),
            'patient_name' => $appointment->patient_name,
            'service' => $appointment->service
                ? ['ar' => $appointment->service->name_ar, 'en' => $appointment->service->name_en]
                : null,
            'doctor' => $appointment->doctor
                ? ['ar' => $appointment->doctor->name_ar, 'en' => $appointment->doctor->name_en]
                : null,
        ];
    }

    /** Guests have no account area to link to; users get their own appointments page. */
    private function actionUrlFor(object $notifiable): ?string
    {
        if (! $notifiable instanceof User) {
            return null;
        }

        $base = rtrim((string) config('app.frontend_url'), '/');

        return $notifiable->isDoctor() ? "{$base}/doctor/appointments" : "{$base}/appointments";
    }

    /**
     * @param  array{ar: string, en: string}|null  $value
     */
    private function localized(?array $value, string $locale): ?string
    {
        return $value ? ($value[$locale] ?? $value['en']) : null;
    }
}
