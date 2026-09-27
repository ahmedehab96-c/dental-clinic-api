<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\BlogPostRequest;
use App\Http\Resources\Admin\BlogPostResource;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BlogPostController extends Controller
{
    /**
     * Unlike the public endpoint, this returns every post — published or
     * still a draft — so admins can manage content before it goes live.
     */
    public function index(Request $request): JsonResponse
    {
        $posts = BlogPost::query()
            ->with(['category', 'author'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $query->where(
                    fn ($q) => $q->where('title_ar', 'like', $term)
                        ->orWhere('title_en', 'like', $term)
                );
            })
            ->when($request->filled('category'), fn ($query) => $query->whereHas(
                'category',
                fn ($q) => $q->where('key', $request->string('category')->value()),
            ))
            ->when($request->filled('status'), function ($query) use ($request) {
                $request->input('status') === 'published'
                    ? $query->whereNotNull('published_at')
                    : $query->whereNull('published_at');
            })
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success(BlogPostResource::collection($posts));
    }

    public function show(BlogPost $post): JsonResponse
    {
        return $this->success(new BlogPostResource($post->load(['category', 'author'])));
    }

    public function store(BlogPostRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['image', 'status']);
        $data['image_path'] = $request->file('image')->store('blog', 'public');
        $data['published_at'] = $this->resolvePublishedAt($request);

        // `read_minutes` isn't in $data unless explicitly sent — refresh()
        // picks up its DB-level default (1) instead of leaving the
        // in-memory model's attribute null until the next fetch.
        $post = BlogPost::create($data)->refresh();

        return $this->success(new BlogPostResource($post->load(['category', 'author'])), 201);
    }

    public function update(BlogPostRequest $request, BlogPost $post): JsonResponse
    {
        $data = $request->safe()->except(['image', 'status']);

        if ($request->hasFile('image')) {
            // Store the new file first — only delete the old one once the
            // new upload has actually succeeded, so a failed upload never
            // leaves the post without any image at all.
            $newPath = $request->file('image')->store('blog', 'public');
            $this->deleteStoredImage($post->image_path);
            $data['image_path'] = $newPath;
        }

        if ($request->filled('status')) {
            $data['published_at'] = $this->resolvePublishedAt($request);
        }

        $post->update($data);

        return $this->success(new BlogPostResource($post->load(['category', 'author'])));
    }

    /**
     * No other table references `blog_posts`, so unlike doctors/services
     * there's no appointment-history-style integrity concern here — a plain
     * delete is safe. Setting a post back to Draft already covers "unpublish
     * without losing it" for admins who want that instead of deleting.
     */
    public function destroy(BlogPost $post): JsonResponse
    {
        $this->deleteStoredImage($post->image_path);
        $post->delete();

        return $this->success();
    }

    /**
     * Draft clears the publish timestamp; Published uses the admin's chosen
     * date/time (for scheduling) and falls back to now() when none is given.
     */
    private function resolvePublishedAt(Request $request): ?string
    {
        if ($request->input('status') === 'draft') {
            return null;
        }

        return $request->filled('published_at') ? $request->input('published_at') : now();
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
