<?php

namespace Sitewyn\Packages\Page\Providers;

use Illuminate\Support\ServiceProvider;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Sitewyn\Core\Base\Support\PermissionRegistry;

class PageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/page.php'), 'page');
    }

    public function boot(): void
    {
        $this->loadViewsFrom($this->modulePath('resources/views'), 'package/page');
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->registerPermissions();
        $this->registerAdminMenu();
    }

    private function modulePath(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2);

        return $path === '' ? $basePath : $basePath.DIRECTORY_SEPARATOR.$path;
    }

    private function registerAdminMenu(): void
    {
        if (! class_exists(AdminMenuRegistry::class)) {
            return;
        }

        $this->app->make(AdminMenuRegistry::class)->add([
            'id' => 'pages',
            'title' => 'Pages',
            'route' => 'admin.pages.index',
            'icon' => 'page',
            'permission' => 'page.index',
            'active' => ['admin.pages.*'],
            'order' => 20,
        ]);
    }

    private function registerPermissions(): void
    {
        if (! class_exists(PermissionRegistry::class)) {
            return;
        }

        $this->app->make(PermissionRegistry::class)->register([
            [
                'key' => 'page.index',
                'name' => 'View pages',
                'group' => 'page',
                'description' => 'View the admin page list and previews.',
            ],
            [
                'key' => 'page.create',
                'name' => 'Create pages',
                'group' => 'page',
                'description' => 'Create admin pages.',
            ],
            [
                'key' => 'page.edit',
                'name' => 'Edit pages',
                'group' => 'page',
                'description' => 'Edit admin pages.',
            ],
            [
                'key' => 'page.delete',
                'name' => 'Delete pages',
                'group' => 'page',
                'description' => 'Delete admin pages.',
            ],
        ], 'package/page');
    }
}
