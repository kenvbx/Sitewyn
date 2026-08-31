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
}
