<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

/**
 * Object-level rules for a single appointment. Role gating (which route
 * groups a user can even reach) lives in EnsureUserHasRole; this only
 * decides ownership once a role is already allowed onto the route —
 * a doctor may only touch their own appointments, a patient only theirs.
 */
class AppointmentPolicy
{
    /**
     * Admins can do anything to any appointment; every other ability below
     * only runs for non-admins.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isDoctor()) {
            return $user->doctor?->id === $appointment->doctor_id;
        }

        return $user->id === $appointment->user_id;
    }

    public function updateStatus(User $user, Appointment $appointment): bool
    {
        return $user->isDoctor() && $user->doctor?->id === $appointment->doctor_id;
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        if (! $user->isPatient() || $user->id !== $appointment->user_id) {
            return false;
        }

        return in_array($appointment->status, ['pending', 'confirmed'], strict: true);
    }
}
