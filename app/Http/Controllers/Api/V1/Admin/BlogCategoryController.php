<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\BlogCategoryRequest;
use App\Http\Resources\BlogCategoryResource;
use App\Models\BlogCategory;
use Illuminate\Http\JsonResponse;

class BlogCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success(BlogCategoryResource::collection(BlogCategory::query()->orderBy('label_en')->get()));
    }

    public function store(BlogCategoryRequest $request): JsonResponse
    {
        $category = BlogCategory::create($request->validated());

        return $this->success(new BlogCategoryResource($category), 201);
    }

    public function update(BlogCategoryRequest $request, BlogCategory $blog_category): JsonResponse
    {
        $blog_category->update($request->validated());

        return $this->success(new BlogCategoryResource($blog_category));
    }

    public function destroy(BlogCategory $blog_category): JsonResponse
    {
        $blog_category->delete();

        return $this->success();
    }
}
