<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = Service::query()
            ->where('is_active', true)
            ->when($request->filled('search'), fn ($query) => $query->where(
                fn ($q) => $q->where('name_en', 'like', '%'.$request->string('search').'%')
                    ->orWhere('name_ar', 'like', '%'.$request->string('search').'%'),
            ))
            ->orderBy('id')
            ->paginate($request->integer('per_page', 12));

        return $this->success(ServiceResource::collection($services));
    }

    /**
     * Manual slug lookup (rather than implicit route-model binding) so a
     * deactivated service 404s here exactly like a deleted one would.
     */
    public function show(string $service): JsonResponse
    {
        $service = Service::where('slug', $service)->where('is_active', true)->firstOrFail();

        return $this->success(new ServiceResource($service->load('doctors')));
    }
}
