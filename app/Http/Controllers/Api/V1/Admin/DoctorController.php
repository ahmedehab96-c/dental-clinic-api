<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\DoctorRequest;
use App\Http\Resources\Admin\DoctorResource;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DoctorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $doctors = Doctor::query()
            ->with('services')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $query->where(
                    fn ($q) => $q->where('name_ar', 'like', $term)
                        ->orWhere('name_en', 'like', $term)
                        ->orWhere('specialty_ar', 'like', $term)
                        ->orWhere('specialty_en', 'like', $term)
                        ->orWhere('email', 'like', $term)
                );
            })
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success(DoctorResource::collection($doctors));
    }

    public function show(Doctor $doctor): JsonResponse
    {
        return $this->success(new DoctorResource($doctor->load('services')));
    }

    public function store(DoctorRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['service_ids', 'photo']);
        $data['slug'] = $this->uniqueSlug($request->validated('name_en'));
        $data['photo_path'] = $request->file('photo')->store('doctors', 'public');

        // `is_active` isn't in $data unless explicitly sent — refresh()
        // picks up its DB-level default (true) instead of leaving the
        // in-memory model's attribute null until the next fetch.
        $doctor = Doctor::create($data)->refresh();

        if ($request->has('service_ids')) {
            $doctor->services()->sync($request->input('service_ids', []));
        }

        return $this->success(new DoctorResource($doctor->load('services')), 201);
    }

    public function update(DoctorRequest $request, Doctor $doctor): JsonResponse
    {
        $data = $request->safe()->except(['service_ids', 'photo']);

        if ($request->hasFile('photo')) {
            $this->deleteStoredPhoto($doctor->photo_path);
            $data['photo_path'] = $request->file('photo')->store('doctors', 'public');
        }

        $doctor->update($data);

        if ($request->has('service_ids')) {
            $doctor->services()->sync($request->input('service_ids', []));
        }

        return $this->success(new DoctorResource($doctor->load('services')));
    }

    /**
     * Deleting a doctor with appointment history would silently orphan
     * those records' doctor reference (the FK is nullOnDelete) — refuse
     * and point the admin at deactivation instead, which keeps the doctor
     * off the public site without losing that history.
     */
    public function destroy(Doctor $doctor): JsonResponse
    {
        if ($doctor->appointments()->exists()) {
            return $this->error(
                'This doctor has appointment history and cannot be deleted. Deactivate them instead.',
                422,
            );
        }

        $this->deleteStoredPhoto($doctor->photo_path);
        $doctor->delete();

        return $this->success();
    }

    /**
     * Seed data uses external demo photo URLs directly (see ApiResource::fileUrl) —
     * only ever delete a path we actually stored on the public disk.
     */
    private function deleteStoredPhoto(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function uniqueSlug(string $nameEn): string
    {
        $base = Str::slug($nameEn);
        $slug = $base;
        $suffix = 2;

        while (Doctor::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
