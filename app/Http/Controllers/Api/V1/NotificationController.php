<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Every query here goes through `$request->user()->notifications()`, so it is
 * scoped to the authenticated account — a notification belonging to someone
 * else simply isn't found (404) rather than being filtered client-side.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['filter' => ['nullable', 'in:unread']]);

        $notifications = $request->user()->notifications()
            ->when($request->input('filter') === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->paginate(min($request->integer('per_page', 15), 50));

        return $this->success(NotificationResource::collection($notifications));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success(['count' => $request->user()->unreadNotifications()->count()]);
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $record = $request->user()->notifications()->findOrFail($notification);
        $record->markAsRead();

        return $this->success(new NotificationResource($record));
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->success(['updated' => $updated]);
    }
}
