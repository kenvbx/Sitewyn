<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Sitewyn\Core\Base\Http\Middleware\ApplySecurityHeaders;
use Sitewyn\Core\Base\Http\Middleware\TrackVisits;
use Sitewyn\Core\Base\Models\RequestLog;
use Symfony\Component\HttpFoundation\Response;

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
        $middleware->web(append: [ApplySecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            try {
                if ($response->getStatusCode() >= 400 && ! Str::startsWith($request->path(), ['admin/request-logs'])) {
                    RequestLog::query()->create([
                        'url' => mb_substr($request->fullUrl(), 0, 2048),
                        'method' => mb_substr($request->method(), 0, 10),
                        'status_code' => $response->getStatusCode(),
                        'ip_address' => $request->ip(),
                        'user_agent' => mb_substr((string) $request->userAgent(), 0, 500) ?: null,
                    ]);
                }
            } catch (Throwable) {
                // Request logging must never break the response being handled.
            }

            return $response;
        });
    })->create();
