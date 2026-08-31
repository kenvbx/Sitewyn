<?php

namespace Sitewyn\Core\Base\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Sitewyn\Core\Base\Models\AnalyticsVisit;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Lightweight visit tracking (MVP): every cacheable GET answered with 2xx on
 * the frontend writes one analytics_visits row — synchronous, no queue. The
 * middleware is prepended to the "web" group, so the row is written in the
 * response phase: by then StartSession has already run and the session id is
 * readable without disturbing any earlier middleware. Admin, infra and SEO
 * paths, bots and every non-2xx answer are skipped, and any failure is
 * swallowed — tracking must never break the tracked request.
 */
class TrackVisits
{
    /**
     * Path prefixes tracked pages must never match: admin area, infra
     * (health check, storage, build assets), public SEO files and the demo
     * plugin surface.
     */
    private const SKIPPED_PREFIXES = [
        'admin',
        '_platform',
        'api',
        'storage',
        'build',
        'vendor',
        'vendor-plugin',
        'sitemap.xml',
        'robots.txt',
        'demo-plugin',
    ];

    private const BOT_USER_AGENT = '/bot|crawl|spider|slurp|bingpreview/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if ($this->shouldTrack($request, $response)) {
                AnalyticsVisit::query()->create([
                    'path' => mb_substr($request->path(), 0, 500),
                    'referer' => $this->capped($request->headers->get('referer')),
                    'user_agent' => $this->capped($request->userAgent()),
                    'ip' => $request->ip(),
                    'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        // isMethodCacheable() is GET/HEAD, which rules out OPTIONS and POSTs.
        if (! $request->isMethodCacheable() || ! $response->isSuccessful()) {
            return false;
        }

        if (Str::startsWith($request->path(), self::SKIPPED_PREFIXES)) {
            return false;
        }

        return preg_match(self::BOT_USER_AGENT, (string) $request->userAgent()) !== 1;
    }

    private function capped(?string $value): ?string
    {
        return $value === null ? null : mb_substr($value, 0, 500);
    }
}
