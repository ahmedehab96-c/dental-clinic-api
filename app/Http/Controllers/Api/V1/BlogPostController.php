<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogPostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = BlogPost::query()
            ->with(['category', 'author'])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($request->filled('category'), fn ($query) => $query->whereHas(
                'category',
                fn ($q) => $q->where('key', $request->string('category')),
            ))
            ->when($request->filled('search'), fn ($query) => $query->where(
                fn ($q) => $q->where('title_en', 'like', '%'.$request->string('search').'%')
                    ->orWhere('title_ar', 'like', '%'.$request->string('search').'%'),
            ))
            ->latest('published_at')
            ->paginate($request->integer('per_page', 9));

        return $this->success(BlogPostResource::collection($posts));
    }

    /**
     * Manual slug lookup (rather than implicit route-model binding) so a
     * draft or not-yet-scheduled post 404s here exactly like a deleted one
     * would — the index already excludes them, this closes the same gap on
     * a directly-guessed detail URL.
     */
    public function show(string $post): JsonResponse
    {
        $post = BlogPost::where('slug', $post)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        return $this->success(new BlogPostResource($post->load(['category', 'author'])));
    }
}
