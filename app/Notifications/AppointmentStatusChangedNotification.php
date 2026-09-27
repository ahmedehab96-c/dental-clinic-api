<?php

namespace App\Notifications;

use App\Models\Appointment;

class AppointmentStatusChangedNotification extends AppointmentNotification
{
    public function __construct(
        Appointment $appointment,
        public readonly string $previousStatus,
        public readonly string $actorRole,
    ) {
        parent::__construct($appointment);
    }

    protected function kind(): string
    {
        return 'appointment_status_changed';
    }

    protected function mailCopyKey(string $audience): string
    {
        $status = $this->snapshot['status'];

        if ($audience === 'doctor') {
            if ($status === 'cancelled') {
                return $this->actorRole === 'patient' ? 'status.doctor.cancelled_by_patient' : 'status.doctor.cancelled';
            }

            return 'status.doctor.other';
        }

        return in_array($status, ['confirmed', 'completed', 'cancelled'], strict: true)
            ? "status.patient.{$status}"
            : 'status.patient.other';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            ...$this->payload(),
            'previous_status' => $this->previousStatus,
            // Who made the change (patient/doctor/admin) — lets the UI say
            // "the patient cancelled" vs. "the clinic confirmed".
            'actor_role' => $this->actorRole,
        ];
    }
}
