<?php

namespace App\Http\Controllers\Api\V1\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointments) {}

    /**
     * Scoped by the authenticated doctor's own linked profile — never by a
     * client-supplied doctor id — so this can only ever return their own
     * appointments. `scope` narrows by date: today / upcoming (after today)
     * / past (before today); omitted means all.
     */
    public function index(Request $request): JsonResponse
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            return $this->error('No doctor profile is linked to this account yet.', 404);
        }

        $request->validate([
            'scope' => ['nullable', Rule::in(['today', 'upcoming', 'past'])],
            'status' => ['nullable', Rule::in(array_keys(Appointment::STATUS_TRANSITIONS))],
        ]);

        $scope = $request->input('scope');
        $today = today()->toDateString();

        $appointments = $doctor->appointments()
            ->with(['service', 'doctor'])
            ->when($scope === 'today', fn ($query) => $query->whereDate('date', $today))
            ->when($scope === 'upcoming', fn ($query) => $query->whereDate('date', '>', $today))
            ->when($scope === 'past', fn ($query) => $query->whereDate('date', '<', $today))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            // Today/upcoming read naturally soonest-first; history reads latest-first.
            ->when(
                in_array($scope, ['today', 'upcoming'], strict: true),
                fn ($query) => $query->orderBy('date')->orderBy('time'),
                fn ($query) => $query->orderByDesc('date')->orderByDesc('time'),
            )
            ->paginate($request->integer('per_page', 15));

        return $this->success(AppointmentResource::collection($appointments));
    }

    public function show(Appointment $appointment): JsonResponse
    {
        $this->authorize('view', $appointment);

        return $this->success(new AppointmentResource($appointment->load(['service', 'doctor'])));
    }

    /**
     * Ownership via AppointmentPolicy::updateStatus, plus the doctor workflow:
     * only the forward moves in Appointment::STATUS_TRANSITIONS are allowed
     * (e.g. no reopening a completed or cancelled visit).
     */
    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('updateStatus', $appointment);

        if (! $appointment->canTransitionTo($request->validated('status'))) {
            return $this->error(
                "An appointment that is {$appointment->status} cannot be changed to {$request->validated('status')}.",
                422,
            );
        }

        $this->appointments->changeStatus($appointment, $request->validated('status'), $request->user());

        return $this->success(new AppointmentResource($appointment->load(['service', 'doctor'])));
    }
}
