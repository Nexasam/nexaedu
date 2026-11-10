<?php

use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureSchoolOnboardingIsComplete;
use App\Http\Middleware\EnsureSchoolPermission;
use App\Http\Middleware\EnsureSchoolRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveSchoolTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'platform.admin' => EnsurePlatformAdmin::class,
            'school.permission' => EnsureSchoolPermission::class,
            'school.ready' => EnsureSchoolOnboardingIsComplete::class,
            'school.role' => EnsureSchoolRole::class,
            'tenant' => ResolveSchoolTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
