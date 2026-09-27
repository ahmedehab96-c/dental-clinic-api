<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\FaqRequest;
use App\Http\Resources\Admin\FaqResource;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Unlike the public endpoint, this returns unpublished FAQs too.
     */
    public function index(Request $request): JsonResponse
    {
        $faqs = Faq::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $query->where(
                    fn ($q) => $q->where('question_ar', 'like', $term)
                        ->orWhere('question_en', 'like', $term)
                        ->orWhere('answer_ar', 'like', $term)
                        ->orWhere('answer_en', 'like', $term)
                );
            })
            ->when($request->filled('is_published'), fn ($query) => $query->where('is_published', $request->boolean('is_published')))
            ->orderBy('sort_order')
            ->paginate($request->integer('per_page', 15));

        return $this->success(FaqResource::collection($faqs));
    }

    public function show(Faq $faq): JsonResponse
    {
        return $this->success(new FaqResource($faq));
    }

    public function store(FaqRequest $request): JsonResponse
    {
        // `is_published` isn't in validated() unless explicitly sent — refresh()
        // picks up its DB-level default (true) instead of leaving the
        // in-memory model's attribute null until the next fetch.
        $faq = Faq::create($request->validated())->refresh();

        return $this->success(new FaqResource($faq), 201);
    }

    public function update(FaqRequest $request, Faq $faq): JsonResponse
    {
        $faq->update($request->validated());

        return $this->success(new FaqResource($faq));
    }

    /**
     * No other table references `faqs`, so a plain delete is safe.
     * `is_published` already gives admins a non-destructive way to hide a
     * FAQ from the public site without losing it — the delete confirmation
     * on the frontend points at that as the alternative.
     */
    public function destroy(Faq $faq): JsonResponse
    {
        $faq->delete();

        return $this->success();
    }
}
