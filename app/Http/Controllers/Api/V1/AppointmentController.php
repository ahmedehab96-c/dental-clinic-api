<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BookAppointmentRequest;
use App\Http\Requests\Api\V1\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointments) {}

    /**
     * Guest-allowed on purpose — matches the current React booking flow,
     * which never requires an account. If the request is authenticated
     * (Sanctum), the appointment is linked to that user automatically.
     *
     * This route has no `auth:sanctum` middleware (it must stay guest-accessible),
     * so nothing ever calls Auth::shouldUse('sanctum') for it — $request->user()
     * would resolve via the app's default guard (session-based `web`) and
     * always be null for a stateless Bearer-token request. Naming the guard
     * explicitly resolves it correctly either way.
     */
    public function store(BookAppointmentRequest $request): JsonResponse
    {
        $appointment = $this->appointments->book([
            ...$request->validated(),
            'user_id' => $request->user('sanctum')?->id,
        ]);

        return $this->success(new AppointmentResource($appointment), 201);
    }

    public function mine(Request $request): JsonResponse
    {
        $appointments = $request->user()
            ->appointments()
            ->with(['service', 'doctor'])
            ->latest('date')
            ->paginate($request->integer('per_page', 10));

        return $this->success(AppointmentResource::collection($appointments));
    }

    /**
     * A single one of the authenticated patient's own appointments.
     * AppointmentPolicy::view rejects anyone else's (including a doctor's
     * or another patient's) with a 403, and admins via its `before` hook.
     */
    public function showMine(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('view', $appointment);

        return $this->success(new AppointmentResource($appointment->load(['service', 'doctor'])));
    }

    /**
     * A patient cancelling their own, still-cancellable appointment.
     * AppointmentPolicy::cancel enforces both the ownership and the
     * pending/confirmed-only status rule.
     */
    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('cancel', $appointment);

        $this->appointments->changeStatus($appointment, 'cancelled', $request->user());

        return $this->success(new AppointmentResource($appointment->load(['service', 'doctor'])));
    }

    /**
     * Shared by the admin and doctor route groups: an admin may set any
     * appointment to any status; a doctor only their own (enforced by
     * AppointmentPolicy::updateStatus).
     */
    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('updateStatus', $appointment);

        $this->appointments->changeStatus($appointment, $request->validated('status'), $request->user());

        return $this->success(new AppointmentResource($appointment->load(['service', 'doctor'])));
    }
}
