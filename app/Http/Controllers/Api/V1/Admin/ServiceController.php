<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ServiceRequest;
use App\Http\Resources\Admin\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = Service::query()
            ->withCount('doctors')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $query->where(
                    fn ($q) => $q->where('name_ar', 'like', $term)
                        ->orWhere('name_en', 'like', $term)
                        ->orWhere('short_description_ar', 'like', $term)
                        ->orWhere('short_description_en', 'like', $term)
                );
            })
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success(ServiceResource::collection($services));
    }

    public function show(Service $service): JsonResponse
    {
        return $this->success(new ServiceResource($service->load('doctors')));
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['doctor_ids', 'image']);
        $data['icon'] = $data['icon'] ?? 'checkup';
        $data['image_path'] = $request->file('image')->store('services', 'public');

        // `is_active` isn't in $data unless explicitly sent — refresh()
        // picks up its DB-level default (true) instead of leaving the
        // in-memory model's attribute null until the next fetch.
        $service = Service::create($data)->refresh();

        if ($request->has('doctor_ids')) {
            $service->doctors()->sync($request->input('doctor_ids', []));
        }

        return $this->success(new ServiceResource($service->load('doctors')), 201);
    }

    public function update(ServiceRequest $request, Service $service): JsonResponse
    {
        $data = $request->safe()->except(['doctor_ids', 'image']);

        if ($request->hasFile('image')) {
            $this->deleteStoredImage($service->image_path);
            $data['image_path'] = $request->file('image')->store('services', 'public');
        }

        $service->update($data);

        if ($request->has('doctor_ids')) {
            $service->doctors()->sync($request->input('doctor_ids', []));
        }

        return $this->success(new ServiceResource($service->load('doctors')));
    }

    /**
     * Deleting a service with appointment history would violate the
     * `appointments.service_id` foreign key (restrictOnDelete) — refuse with
     * a clean 422 and point the admin at deactivation instead, which keeps
     * the service off the public site without losing that history.
     */
    public function destroy(Service $service): JsonResponse
    {
        if ($service->appointments()->exists()) {
            return $this->error(
                'This service has appointment history and cannot be deleted. Deactivate it instead.',
                422,
            );
        }

        $this->deleteStoredImage($service->image_path);
        $service->delete();

        return $this->success();
    }

    /**
     * Seed data uses external demo image URLs directly (see ApiResource::fileUrl) —
     * only ever delete a path we actually stored on the public disk.
     */
    private function deleteStoredImage(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }
}
