<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $doctors = Doctor::query()
            ->where('is_active', true)
            ->with('services')
            ->when($request->filled('service'), fn ($query) => $query->whereHas(
                'services',
                fn ($q) => $q->where('slug', $request->string('service')),
            ))
            ->when($request->filled('featured'), fn ($query) => $query->where('featured', $request->boolean('featured')))
            ->latest('id')
            ->paginate($request->integer('per_page', 12));

        return $this->success(DoctorResource::collection($doctors));
    }

    /**
     * Takes the slug as a plain string (not an implicit `Doctor $doctor`
     * binding) so an inactive doctor's page can 404 like it doesn't exist,
     * rather than the binding resolving it before this method ever runs.
     */
    public function show(string $doctor): JsonResponse
    {
        $doctor = Doctor::where('slug', $doctor)->where('is_active', true)->firstOrFail();

        return $this->success(new DoctorResource($doctor->load('services')));
    }
}
