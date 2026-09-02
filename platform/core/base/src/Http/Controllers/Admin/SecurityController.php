<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Http\Middleware\ApplySecurityHeaders;

class SecurityController extends Controller
{
    public function __invoke(): View
    {
        $settings = [
            [
                'name' => 'HttpOnly Cookie Flag',
                'description' => 'Prevents JavaScript from accessing session cookies (protects against XSS attacks)',
                'current' => config('session.http_only') === true ? 'true' : 'false',
                'recommended' => 'true',
            ],
            [
                'name' => 'Secure Cookie Flag',
                'description' => 'Ensures cookies are only sent over HTTPS connections',
                'current' => config('session.secure') === true ? 'true' : 'false',
                'recommended' => 'true',
            ],
            [
                'name' => 'SameSite Cookie Flag',
                'description' => 'Prevents CSRF attacks by restricting when cookies are sent',
                'current' => (string) config('session.same_site', 'lax'),
                'recommended' => 'lax',
            ],
            [
                'name' => 'HTTP Security Headers',
                'description' => 'Adds security headers to protect against common web vulnerabilities',
                'current' => ApplySecurityHeaders::HEADERS === [] ? 'false' : 'true',
                'recommended' => 'true',
            ],
        ];

        return view('core/base::admin.security.index', [
            'settings' => $settings,
            'headers' => ApplySecurityHeaders::HEADERS,
            'allConfigured' => collect($settings)->every(
                fn (array $setting): bool => $setting['current'] === $setting['recommended']
            ),
        ]);
    }
}
