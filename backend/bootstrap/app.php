<?php

use App\Http\JsonApiExceptionRenderer;
use App\Http\Middleware\AuthenticatePartner;
use App\Http\Middleware\JsonApiContentType;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

// PHP adds this header itself before Laravel starts, so it is dropped here. This covers artisan serve locally and in Docker.
header_remove('X-Powered-By');

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [JsonApiContentType::class]);
        $middleware->alias(['partner' => AuthenticatePartner::class]);
        // Route bindings look records up within the authenticated partner, so authentication runs first: 401 before any lookup.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: AuthenticatePartner::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(new JsonApiExceptionRenderer);
    })->create();
