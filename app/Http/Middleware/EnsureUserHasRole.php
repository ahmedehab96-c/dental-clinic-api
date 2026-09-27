<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route group to one or more roles, e.g. `role:admin` or
 * `role:admin,doctor`. Always runs after `auth:sanctum`, so the user is
 * already authenticated — this only decides whether their role is allowed.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, strict: true)) {
            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ], 403, options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $next($request);
    }
}
