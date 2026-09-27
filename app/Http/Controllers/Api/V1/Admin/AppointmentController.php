<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $appointments = Appointment::query()
            ->with(['service', 'doctor', 'user'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('doctor_id'), fn ($query) => $query->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('service_id'), fn ($query) => $query->where('service_id', $request->integer('service_id')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('date', $request->string('date')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $query->where(fn ($q) => $q->where('patient_name', 'like', $term)
                    ->orWhere('patient_phone', 'like', $term)
                    ->orWhere('patient_email', 'like', $term));
            })
            ->latest('date')
            ->paginate($request->integer('per_page', 15));

        return $this->success(AppointmentResource::collection($appointments));
    }

    public function show(Appointment $appointment): JsonResponse
    {
        return $this->success(new AppointmentResource($appointment->load(['service', 'doctor', 'user'])));
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->delete();

        return $this->success();
    }
}
