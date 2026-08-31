<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sitewyn\Core\Base\Http\Middleware\TrackVisits;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands()
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectTo(
            guests: fn (Request $request) => $request->is('admin*') ? route('admin.login') : '/login',
            users: fn (Request $request) => $request->is('admin*') ? route('admin.dashboard') : '/',
        );

        // Frontend visit tracking (core/base TrackVisits). Prepend puts it at
        // the head of the web group; the visit row is written in the response
        // phase, after StartSession has run, so the session id is available.
        $middleware->web(prepend: [TrackVisits::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
