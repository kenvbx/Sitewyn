<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Exceptions\PluginDependencyException;
use Sitewyn\Core\Base\Exceptions\PluginMigrationFailedException;
use Sitewyn\Core\Base\Support\PluginActivator;
use Sitewyn\Core\Base\Support\PluginManager;

class PluginManageController extends Controller
{
    public function __construct(
        private readonly PluginManager $manager,
        private readonly PluginActivator $activator,
    ) {}

    public function index(): View
    {
        return view('core/base::admin.plugins.index', [
            'plugins' => $this->manager->all(),
        ]);
    }

    public function activate(string $slug): RedirectResponse
    {
        $manifest = $this->resolvedManifest($slug);

        if ($manifest['isActive']) {
            admin_flash()->info("Plugin [{$manifest['name']}] is already active.");

            return redirect()->route('admin.plugins.index');
        }

        try {
            $this->activator->activate($slug);
        } catch (PluginDependencyException|PluginMigrationFailedException $e) {
            admin_flash()->error($e->getMessage());

            return redirect()->route('admin.plugins.index');
        }

        admin_flash()->success("Plugin [{$manifest['name']}] activated.");

        return redirect()->route('admin.plugins.index');
    }

    public function deactivate(string $slug): RedirectResponse
    {
        $manifest = $this->resolvedManifest($slug);

        if (! $manifest['isActive']) {
            admin_flash()->info("Plugin [{$manifest['name']}] is already inactive.");

            return redirect()->route('admin.plugins.index');
        }

        try {
            // The UI always keeps plugin data; dropping it (--rollback) stays CLI-only.
            $this->activator->deactivate($slug);
        } catch (PluginDependencyException|PluginMigrationFailedException $e) {
            admin_flash()->error($e->getMessage());

            return redirect()->route('admin.plugins.index');
        }

        admin_flash()->success("Plugin [{$manifest['name']}] deactivated. Its data was kept.");

        return redirect()->route('admin.plugins.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvedManifest(string $slug): array
    {
        $manifest = $this->manager->find($slug);

        if ($manifest === null) {
            abort(404, "Plugin [{$slug}] does not exist.");
        }

        return $manifest;
    }
}
