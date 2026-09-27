<?php

namespace App\Notifications;

class AppointmentCreatedNotification extends AppointmentNotification
{
    protected function kind(): string
    {
        return 'appointment_created';
    }

    protected function mailCopyKey(string $audience): string
    {
        return "created.{$audience}";
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }
}
