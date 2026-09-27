<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\TestimonialRequest;
use App\Http\Resources\Admin\TestimonialResource;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestimonialController extends Controller
{
    /**
     * Unlike the public endpoint, this returns unpublished testimonials too.
     */
    public function index(Request $request): JsonResponse
    {
        $testimonials = Testimonial::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $query->where(
                    fn ($q) => $q->where('patient_name_ar', 'like', $term)
                        ->orWhere('patient_name_en', 'like', $term)
                        ->orWhere('quote_ar', 'like', $term)
                        ->orWhere('quote_en', 'like', $term)
                );
            })
            ->when($request->filled('rating'), fn ($query) => $query->where('rating', $request->integer('rating')))
            ->when($request->filled('is_published'), fn ($query) => $query->where('is_published', $request->boolean('is_published')))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success(TestimonialResource::collection($testimonials));
    }

    public function show(Testimonial $testimonial): JsonResponse
    {
        return $this->success(new TestimonialResource($testimonial));
    }

    public function store(TestimonialRequest $request): JsonResponse
    {
        $data = $request->safe()->except('photo');
        $data['photo_path'] = $request->file('photo')->store('testimonials', 'public');

        // `is_published` isn't in $data unless explicitly sent — refresh()
        // picks up its DB-level default (true) instead of leaving the
        // in-memory model's attribute null until the next fetch.
        $testimonial = Testimonial::create($data)->refresh();

        return $this->success(new TestimonialResource($testimonial), 201);
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): JsonResponse
    {
        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            // Store the new file first — only delete the old one once the
            // new upload has actually succeeded, so a failed upload never
            // leaves the testimonial without any photo at all.
            $newPath = $request->file('photo')->store('testimonials', 'public');
            $this->deleteStoredPhoto($testimonial->photo_path);
            $data['photo_path'] = $newPath;
        }

        $testimonial->update($data);

        return $this->success(new TestimonialResource($testimonial));
    }

    /**
     * No other table references `testimonials`, so a plain delete is safe.
     * `is_published` already gives admins a non-destructive way to hide a
     * testimonial from the public site without losing it — the delete
     * confirmation on the frontend points at that as the alternative.
     */
    public function destroy(Testimonial $testimonial): JsonResponse
    {
        $this->deleteStoredPhoto($testimonial->photo_path);
        $testimonial->delete();

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
}
