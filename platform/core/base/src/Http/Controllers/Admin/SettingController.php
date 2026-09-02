<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Http\Requests\Admin\UpdateSettingsRequest;
use Sitewyn\Core\Base\Support\RobotsTxt;
use Sitewyn\Core\Base\Support\SettingStore;
use Sitewyn\Core\Base\Support\ThemeManager;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingStore $settings,
        private readonly ThemeManager $themes,
    ) {}

    public function edit(): View
    {
        return view('core/base::admin.settings.edit', [
            'settings' => [
                'site_name' => $this->settings->get('site_name', config('app.name', 'Sitewyn')),
                'site_logo' => $this->settings->get('site_logo'),
                // Prefill the live robots.txt body (default when unconfigured)
                // so what the admin sees is what crawlers get.
                'robots_txt' => RobotsTxt::content($this->settings->get('robots_txt')),
                'active_theme' => $this->settings->get('active_theme', ThemeManager::DEFAULT_THEME),
            ],
            'themeOptions' => $this->themes->all()->pluck('name', 'slug')->all(),
            'sections' => $this->sections(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->setMany([
            'site_name' => $validated['site_name'],
            'site_logo' => $validated['site_logo'] ?? null,
            // A cleared robots.txt stores null so the live default kicks back in.
            'robots_txt' => $validated['robots_txt'] ?? null,
            // Absent field keeps the current theme (validation allows null).
            'active_theme' => $validated['active_theme'] ?? $this->settings->get('active_theme', ThemeManager::DEFAULT_THEME),
        ]);
        $this->settings->applyApplicationConfig();

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.edit');
    }

    /**
     * @return array<int, array{title: string, items: array<int, array{title: string, description: string, icon: string, url: string}>}>
     */
    private function sections(): array
    {
        return [
            [
                'title' => 'Common',
                'items' => [
                    ['title' => 'General', 'description' => 'View and update your general settings and activate license', 'icon' => 'settings', 'url' => '#'],
                    ['title' => 'Email', 'description' => 'View and update your email settings and email templates', 'icon' => 'request-log', 'url' => '#'],
                    ['title' => 'Email templates', 'description' => 'Email templates using HTML & system variables.', 'icon' => 'request-log', 'url' => '#'],
                    ['title' => 'Email rules', 'description' => 'Configure email rules for validation', 'icon' => 'request-log', 'url' => '#'],
                    ['title' => 'Phone Number', 'description' => 'Configure phone number field settings', 'icon' => 'users', 'url' => '#'],
                    ['title' => 'Media', 'description' => 'View and update your media settings', 'icon' => 'category', 'url' => route('admin.media.index', [], false)],
                    ['title' => 'Permalink', 'description' => 'View and update your permalink settings', 'icon' => 'route', 'url' => '#'],
                    ['title' => 'Languages', 'description' => 'View and update your website languages', 'icon' => 'globe', 'url' => route('admin.settings.languages.index', [], false)],
                    ['title' => 'Admin appearance', 'description' => 'View and update logo, favicon, layout,...', 'icon' => 'settings', 'url' => '#'],
                    ['title' => 'API Settings', 'description' => 'View and update your API settings', 'icon' => 'key', 'url' => '#'],
                    ['title' => 'Cache', 'description' => 'Configure caching for optimized speed', 'icon' => 'reload', 'url' => route('admin.system.cache.index', [], false)],
                    ['title' => 'Datatables', 'description' => 'Settings for datatables', 'icon' => 'database', 'url' => '#'],
                    ['title' => 'Website Tracking', 'description' => 'Choose your preferred analytics and tracking method. Only one option can be active at a time.', 'icon' => 'globe', 'url' => '#'],
                    ['title' => 'Optimize', 'description' => 'Minify HTML output, inline CSS, remove comments...', 'icon' => 'bolt', 'url' => '#'],
                ],
            ],
            [
                'title' => 'Localization',
                'items' => [
                    ['title' => 'Locales', 'description' => 'View, download and import locales', 'icon' => 'globe', 'url' => route('admin.settings.languages.index', [], false)],
                    ['title' => 'Theme Translations', 'description' => 'Manage the theme translations', 'icon' => 'globe', 'url' => '#'],
                    ['title' => 'Other Translations', 'description' => 'Manage the other translations (admin, plugins, packages...)', 'icon' => 'request-log', 'url' => '#'],
                ],
            ],
            [
                'title' => 'Others',
                'items' => [
                    ['title' => 'FOB Comment', 'description' => 'Configure settings for FOB Comment', 'icon' => 'request-log', 'url' => '#'],
                    ['title' => 'Social Login', 'description' => 'View and update your social login settings', 'icon' => 'users', 'url' => '#'],
                    ['title' => 'Blog', 'description' => 'View and update blog settings', 'icon' => 'post', 'url' => '#'],
                    ['title' => 'Contact', 'description' => 'Settings for contact plugin', 'icon' => 'request-log', 'url' => '#'],
                    ['title' => 'Captcha', 'description' => 'View and update reCAPTCHA and Math CAPTCHA.', 'icon' => 'reload', 'url' => '#'],
                    ['title' => 'Google Analytics', 'description' => 'Config Credentials for Google Analytics', 'icon' => 'audit', 'url' => '#'],
                    ['title' => 'Member', 'description' => 'View and update member settings', 'icon' => 'users', 'url' => '#'],
                ],
            ],
        ];
    }
}
