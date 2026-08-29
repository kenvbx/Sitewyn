<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Http\Requests\Admin\UpdateSettingsRequest;
use Sitewyn\Core\Base\Support\SettingStore;

class SettingController extends Controller
{
    public function __construct(private readonly SettingStore $settings) {}

    public function edit(): View
    {
        return view('core/base::admin.settings.edit', [
            'settings' => [
                'site_name' => $this->settings->get('site_name', config('app.name', 'Sitewyn')),
                'site_logo' => $this->settings->get('site_logo'),
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->setMany([
            'site_name' => $validated['site_name'],
            'site_logo' => $validated['site_logo'] ?? null,
        ]);
        $this->settings->applyApplicationConfig();

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.edit');
    }
}
