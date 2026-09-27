<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimeSlotResource;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * Without `date`: the next bookable dates (feeds the booking form's date
     * picker). With `date` (+ optional `doctor_id`): that day's time slots.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->filled('date')) {
            return $this->success(['dates' => $this->availability->nextAvailableDates()]);
        }

        $slots = $this->availability->slotsFor(
            $request->string('date')->toString(),
            $request->filled('doctor_id') ? $request->integer('doctor_id') : null,
        );

        return $this->success(TimeSlotResource::collection($slots));
    }
}
