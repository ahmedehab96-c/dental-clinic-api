<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GalleryCaseResource;
use App\Models\GalleryCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryCaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cases = GalleryCase::query()
            ->with('service')
            ->where('is_published', true)
            ->when($request->filled('service'), fn ($query) => $query->whereHas(
                'service',
                fn ($q) => $q->where('slug', $request->string('service')),
            ))
            ->latest('id')
            ->paginate($request->integer('per_page', 9));

        return $this->success(GalleryCaseResource::collection($cases));
    }
}
