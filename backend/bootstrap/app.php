<?php

use App\Exceptions\InvalidCredentialsException;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTenant;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Resolve the request locale (Accept-Language) for every API request.
        $middleware->api(prepend: [
            SetLocale::class,
        ]);

        // Named middleware used by routes.
        $middleware->alias([
            'tenant' => SetTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render a consistent, localized JSON envelope for API requests.
        $isApi = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->render(function (InvalidCredentialsException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error(__('auth.failed'), 401);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error(__('messages.validation_failed'), 422, $e->errors());
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error(__('auth.unauthenticated'), 401);
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error(__('messages.forbidden'), 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException|RouteNotFoundException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error(__('messages.not_found'), 404);
            }
        });
    })->create();
