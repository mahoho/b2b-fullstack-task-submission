// bootstrap/app.php
<?php

use App\Http\JsonApiExceptionRenderer;
use App\Http\Middleware\AuthenticatePartner;
use App\Http\Middleware\JsonApiContentType;
use App\Http\RouteBindings;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [JsonApiContentType::class]);
        $middleware->alias(['partner' => AuthenticatePartner::class]);
        // Bindings are scoped to the authenticated partner, so authentication must run first (401 before any lookup).
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: AuthenticatePartner::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(new JsonApiExceptionRenderer());
    })
    ->booted(fn () => RouteBindings::register())
    ->create();
