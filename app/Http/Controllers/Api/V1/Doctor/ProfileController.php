<?php

namespace App\Http\Controllers\Api\V1\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Doctor\UpdateDoctorProfileRequest;
use App\Http\Resources\Admin\DoctorResource;
use App\Http\Resources\ServiceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Always resolved from the authenticated user's own doctor profile —
     * never from a client-supplied doctor id — so a doctor can only ever
     * see their own record here. Uses the admin-shaped resource since the
     * doctor is looking at their own contact details and status.
     */
    public function show(Request $request): JsonResponse
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            return $this->error('No doctor profile is linked to this account yet.', 404);
        }

        return $this->success(new DoctorResource($doctor->load('services')));
    }

    /**
     * Only the self-editable content fields in UpdateDoctorProfileRequest can
     * change — services, active/featured flags, slug, rating and the linked
     * account stay admin-managed because validated() never carries them.
     */
    public function update(UpdateDoctorProfileRequest $request): JsonResponse
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            return $this->error('No doctor profile is linked to this account yet.', 404);
        }

        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            // Store the new file first; only then drop the old one.
            $newPath = $request->file('photo')->store('doctors', 'public');
            $oldPath = $doctor->photo_path;
            $data['photo_path'] = $newPath;

            if ($oldPath && ! str_starts_with($oldPath, 'http')) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $doctor->update($data);

        return $this->success(new DoctorResource($doctor->load('services')));
    }

    public function services(Request $request): JsonResponse
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            return $this->error('No doctor profile is linked to this account yet.', 404);
        }

        return $this->success(ServiceResource::collection($doctor->services));
    }
}
