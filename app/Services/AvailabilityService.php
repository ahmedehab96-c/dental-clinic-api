<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;

/**
 * Computes bookable time slots for a given date (and optionally a specific
 * doctor), replacing the frontend's mock `timeSlots.js`. The clinic is
 * closed Fridays, matching the current site's published working hours.
 */
class AvailabilityService
{
    private const SLOTS = [
        '09:00', '10:00', '11:00', '12:00', '14:00',
        '15:00', '16:00', '17:00', '18:00', '19:00', '20:00',
    ];

    private const CLOSED_WEEKDAY = Carbon::FRIDAY;

    /**
     * @return array<int, array{time: string, available: bool}>
     */
    public function slotsFor(string $date, ?int $doctorId = null): array
    {
        if (Carbon::parse($date)->dayOfWeek === self::CLOSED_WEEKDAY) {
            return [];
        }

        $bookedTimes = [];

        if ($doctorId !== null) {
            $bookedTimes = Appointment::query()
                ->where('doctor_id', $doctorId)
                ->whereDate('date', $date)
                ->whereIn('status', ['pending', 'confirmed'])
                ->pluck('time')
                ->map(fn ($time) => substr((string) $time, 0, 5))
                ->all();
        }

        return array_map(fn (string $time) => [
            'time' => $time,
            'available' => ! in_array($time, $bookedTimes, true),
        ], self::SLOTS);
    }

    public function isSlotAvailable(string $date, string $time, ?int $doctorId): bool
    {
        if (Carbon::parse($date)->dayOfWeek === self::CLOSED_WEEKDAY || ! in_array($time, self::SLOTS, true)) {
            return false;
        }

        if ($doctorId === null) {
            return true;
        }

        return ! Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->where('time', $time)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();
    }

    /**
     * @return string[] Next N bookable dates (skipping Fridays), as Y-m-d.
     */
    public function nextAvailableDates(int $daysAhead = 14): array
    {
        $dates = [];
        $cursor = Carbon::tomorrow();

        while (count($dates) < $daysAhead) {
            if ($cursor->dayOfWeek !== self::CLOSED_WEEKDAY) {
                $dates[] = $cursor->toDateString();
            }
            $cursor = $cursor->addDay();
        }

        return $dates;
    }
}
