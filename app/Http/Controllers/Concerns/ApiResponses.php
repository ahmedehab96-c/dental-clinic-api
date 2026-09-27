<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Single place every API controller builds its JSON envelope from, so the
 * response shape stays identical across every endpoint for both the React
 * and Flutter clients:
 *   { "success": true,  "data": ..., "meta"?: {...} }
 *   { "success": false, "message": "...", "errors"?: {...} }
 */
trait ApiResponses
{
    protected function success(JsonResource|AnonymousResourceCollection|array|null $data = null, int $status = 200): JsonResponse
    {
        $payload = ['success' => true];

        if ($data instanceof AnonymousResourceCollection && $data->resource instanceof LengthAwarePaginator) {
            $paginated = $data->response()->getData(true);
            $payload['data'] = $paginated['data'];
            $payload['meta'] = $paginated['meta'];
        } else {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status, options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $payload = ['success' => false, 'message' => $message];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status, options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
