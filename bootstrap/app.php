<?php

use App\Exceptions\SlotUnavailableException;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Pure JSON API, no web login route to redirect guests to — without
        // this, Laravel's default Authenticate middleware tries route('login')
        // for any unauthenticated request that omits an Accept header (most
        // raw HTTP clients), crashing with a 500 instead of a clean 401.
        $middleware->redirectGuestsTo(fn () => null);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApi = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($isApi);

        // Every error the API returns — validation, auth, 404, or anything
        // else — comes back as { success: false, message, errors? }, the
        // same envelope success() uses, so React and Flutter parse one shape.
        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], $e->status, options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401, options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        });

        $exceptions->render(function (SlotUnavailableException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409, options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
            ], 404, options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'An error occurred.',
            ], $e->getStatusCode(), options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        });
    })->create();
