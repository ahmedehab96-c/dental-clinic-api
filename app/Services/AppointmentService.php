<?php

namespace App\Services;

use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentCreatedNotification;
use App\Notifications\AppointmentNotification;
use App\Notifications\AppointmentStatusChangedNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

/**
 * The one place appointment booking and status changes happen, so the
 * double-booking guard, reference generation and notifications live in a
 * single spot rather than being duplicated across the public, patient,
 * doctor and admin endpoints.
 */
class AppointmentService
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * @param  array{service_id:int, doctor_id:?int, user_id:?int, patient_name:string, patient_phone:string, patient_email:string, date:string, time:string, notes:?string}  $data
     *
     * @throws SlotUnavailableException
     */
    public function book(array $data): Appointment
    {
        if (! $this->availability->isSlotAvailable($data['date'], $data['time'], $data['doctor_id'] ?? null)) {
            throw new SlotUnavailableException;
        }

        try {
            $appointment = DB::transaction(fn () => Appointment::create([
                ...$data,
                'reference' => $this->generateReference(),
                'status' => 'pending',
            ]));
        } catch (UniqueConstraintViolationException) {
            // Another request booked the same doctor/date/time between our
            // availability check and the insert — surface the same friendly
            // error rather than a raw 500.
            throw new SlotUnavailableException;
        }

        // Only reached once the insert has committed — a failed booking
        // never notifies anyone. The patient's own account gets a receipt.
        $this->notify(
            $appointment,
            $this->stakeholders($appointment, includeAdmins: true),
            new AppointmentCreatedNotification($appointment),
        );

        return $appointment;
    }

    /**
     * Applies an already-authorized, already-validated status change and
     * notifies everyone involved except the person who made it. Re-saving the
     * current status is a no-op, so repeated requests can't spam anyone.
     */
    public function changeStatus(Appointment $appointment, string $status, User $actor): Appointment
    {
        $previous = $appointment->status;

        if ($previous === $status) {
            return $appointment;
        }

        $appointment->update(['status' => $status]);

        $recipients = $this->stakeholders($appointment, includeAdmins: $actor->isPatient())
            ->reject(fn (User $user) => $user->is($actor));

        $this->notify(
            $appointment,
            $recipients,
            new AppointmentStatusChangedNotification($appointment, $previous, $actor->role),
        );

        return $appointment;
    }

    /**
     * The patient's account (if they booked while logged in), the linked
     * doctor's account (if the doctor profile has one) and optionally every
     * admin — de-duplicated so nobody is notified twice for one action.
     *
     * @return Collection<int, User>
     */
    private function stakeholders(Appointment $appointment, bool $includeAdmins): Collection
    {
        $appointment->loadMissing(['user', 'doctor.user']);

        $users = collect([$appointment->user, $appointment->doctor?->user])->filter();

        if ($includeAdmins) {
            $users = $users->merge(User::where('role', 'admin')->get());
        }

        return $users->unique('id')->values();
    }

    /**
     * In-app notifications are written immediately; email is queued (see
     * AppointmentNotification). A guest booking has no account, so its
     * patient is reached through an on-demand mail route to the address they
     * booked with. Any delivery problem is reported, never re-thrown — the
     * appointment change has already succeeded and must stay that way.
     *
     * @param  Collection<int, User>  $users
     */
    private function notify(Appointment $appointment, Collection $users, AppointmentNotification $notification): void
    {
        try {
            Notification::send($users, $notification);

            if ($appointment->user_id === null && $appointment->patient_email) {
                Notification::route('mail', $appointment->patient_email)->notify($notification);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function generateReference(): string
    {
        return 'APT-'.strtoupper(Str::random(6));
    }
}
