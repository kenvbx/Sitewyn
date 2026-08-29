<?php

namespace Sitewyn\Core\Base\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user('admin') ?? $request->user();

        if (! $user || ! method_exists($user, 'hasAnyPermission')) {
            abort(403);
        }

        if ($permissions === [] || ! $user->hasAnyPermission($permissions)) {
            abort(403);
        }

        return $next($request);
    }
}
