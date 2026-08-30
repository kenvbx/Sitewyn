<?php

namespace Sitewyn\Packages\Media\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Sitewyn\Core\Base\Support\PermissionRegistry;
use Sitewyn\Packages\Media\Support\DnsResolver;
use Sitewyn\Packages\Media\Support\PhpDnsResolver;
use Sitewyn\Packages\Media\View\Components\Admin\MediaPicker;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/media.php'), 'media');
        $this->app->bind(DnsResolver::class, PhpDnsResolver::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom($this->modulePath('resources/views'), 'package/media');
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->registerBladeComponents();
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
            'id' => 'media',
            'title' => 'Media',
            'route' => 'admin.media.index',
            'icon' => 'media',
            'permission' => 'media.index',
            'active' => ['admin.media.*'],
            'order' => 30,
        ]);
    }

    private function registerPermissions(): void
    {
        if (! class_exists(PermissionRegistry::class)) {
            return;
        }

        $this->app->make(PermissionRegistry::class)->register([
            [
                'key' => 'media.index',
                'name' => 'View media',
                'group' => 'media',
                'description' => 'View the media library and media picker.',
            ],
            [
                'key' => 'media.upload',
                'name' => 'Upload media',
                'group' => 'media',
                'description' => 'Upload files and create media folders.',
            ],
            [
                'key' => 'media.edit',
                'name' => 'Edit media',
                'group' => 'media',
                'description' => 'Rename and move media files or folders.',
            ],
            [
                'key' => 'media.delete',
                'name' => 'Delete media',
                'group' => 'media',
                'description' => 'Delete media files or folders.',
            ],
        ], 'package/media');
    }

    private function registerBladeComponents(): void
    {
        Blade::component(MediaPicker::class, 'media-picker');
    }
}
