<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\GalleryCaseRequest;
use App\Http\Resources\Admin\GalleryCaseResource;
use App\Models\GalleryCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryCaseController extends Controller
{
    /**
     * Unlike the public endpoint, this returns unpublished cases too.
     */
    public function index(Request $request): JsonResponse
    {
        $cases = GalleryCase::query()
            ->with('service')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $query->where(
                    fn ($q) => $q->where('title_ar', 'like', $term)
                        ->orWhere('title_en', 'like', $term)
                );
            })
            ->when($request->filled('service'), fn ($query) => $query->whereHas(
                'service',
                fn ($q) => $q->where('slug', $request->string('service')->value()),
            ))
            ->when($request->filled('is_published'), fn ($query) => $query->where('is_published', $request->boolean('is_published')))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success(GalleryCaseResource::collection($cases));
    }

    public function show(GalleryCase $gallery_case): JsonResponse
    {
        return $this->success(new GalleryCaseResource($gallery_case->load('service')));
    }

    public function store(GalleryCaseRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['before_image', 'after_image']);
        $data['before_image_path'] = $request->file('before_image')->store('gallery', 'public');
        $data['after_image_path'] = $request->file('after_image')->store('gallery', 'public');

        // `is_published` isn't in $data unless explicitly sent — refresh()
        // picks up its DB-level default (true) instead of leaving the
        // in-memory model's attribute null until the next fetch.
        $case = GalleryCase::create($data)->refresh();

        return $this->success(new GalleryCaseResource($case->load('service')), 201);
    }

    public function update(GalleryCaseRequest $request, GalleryCase $gallery_case): JsonResponse
    {
        $data = $request->safe()->except(['before_image', 'after_image']);

        // Each side replaces independently — store the new file first and
        // only delete the old one once the new upload has actually
        // succeeded, so a failed upload never leaves either side blank.
        if ($request->hasFile('before_image')) {
            $newPath = $request->file('before_image')->store('gallery', 'public');
            $this->deleteStoredImage($gallery_case->before_image_path);
            $data['before_image_path'] = $newPath;
        }
        if ($request->hasFile('after_image')) {
            $newPath = $request->file('after_image')->store('gallery', 'public');
            $this->deleteStoredImage($gallery_case->after_image_path);
            $data['after_image_path'] = $newPath;
        }

        $gallery_case->update($data);

        return $this->success(new GalleryCaseResource($gallery_case->load('service')));
    }

    /**
     * No other table references `gallery_cases`, so a plain delete is safe.
     * `is_published` already gives admins a non-destructive way to hide a
     * case from the public site without losing it — the delete confirmation
     * on the frontend points at that as the alternative.
     */
    public function destroy(GalleryCase $gallery_case): JsonResponse
    {
        $this->deleteStoredImage($gallery_case->before_image_path);
        $this->deleteStoredImage($gallery_case->after_image_path);
        $gallery_case->delete();

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
