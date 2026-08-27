<?php

use App\Http\Middleware\EnsureVendorAccess;
use App\Http\Middleware\SetDeliveryLocation;
use App\Http\Middleware\SetDisplayCurrency;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\ShareStorefrontNavigation;
use App\Support\Api\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->authenticateSessions();
        $middleware->web(append: [
            SetLocale::class,
            SetDisplayCurrency::class,
            SetDeliveryLocation::class,
            ShareStorefrontNavigation::class,
        ]);
        $middleware->alias(['vendor.access' => EnsureVendorAccess::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::exception($e);
        });
    })->create();
