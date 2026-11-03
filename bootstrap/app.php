<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The API has no login page (the Next.js app has), so a guest is answered with a JSON 401, never redirected.
        $middleware->redirectGuestsTo(fn (): ?string => null);
        // Trusted proxies (config/trustedproxy.php) only tell the client address; host and scheme stay the API's own.
        $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
