<?php

namespace Sitewyn\Core\Base\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Sitewyn\Core\Base\Console\Commands\SyncPermissionsCommand;
use Sitewyn\Core\Base\Http\Middleware\CheckPermission;
use Sitewyn\Core\Base\Support\AdminFlash;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Sitewyn\Core\Base\Support\ModuleProviderRepository;
use Sitewyn\Core\Base\Support\PermissionRegistry;
use Sitewyn\Core\Base\Support\SettingStore;
use Sitewyn\Core\Base\View\Components\Admin\Alert;
use Sitewyn\Core\Base\View\Components\Admin\Card;
use Sitewyn\Core\Base\View\Components\Admin\DataTable;
use Sitewyn\Core\Base\View\Components\Admin\FormGroup;
use Sitewyn\Core\Base\View\Components\Admin\Modal;
use Sitewyn\Core\Base\View\Components\Admin\Pagination;
use Sitewyn\Core\Base\View\Components\Admin\Toast;

class BaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/sitewyn-base.php'), 'sitewyn-base');
        $this->app->singleton(AdminFlash::class);
        $this->app->singleton(AdminMenuRegistry::class);
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(SettingStore::class);
        $this->registerModuleProviders();
    }

    public function boot(): void
    {
        $this->loadViewsFrom($this->modulePath('resources/views'), 'core/base');
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->registerBladeComponents();
        $this->registerPasswordResetUrl();
        $this->registerPermissionGate();
        $this->registerRouteMiddleware();
        $this->registerCorePermissions();
        $this->registerCoreAdminMenu();
        $this->applyApplicationSettings();
        $this->registerCommands();
    }

    private function modulePath(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2);

        return $path === '' ? $basePath : $basePath.DIRECTORY_SEPARATOR.$path;
    }

    private function registerModuleProviders(): void
    {
        $repository = new ModuleProviderRepository(base_path());
        $excludedProviders = config('sitewyn-base.modules.excluded_providers', []);

        foreach ($repository->providers(config('sitewyn-base.modules.provider_roots', [])) as $provider) {
            if (in_array($provider, $excludedProviders, true)) {
                continue;
            }

            if (isset($this->app->getLoadedProviders()[$provider])) {
                continue;
            }

            $this->app->register($provider);
        }
    }

    private function registerPasswordResetUrl(): void
    {
        ResetPassword::createUrlUsing(fn (object $notifiable, string $token): string => route('admin.password.reset', [
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]));
    }

    private function registerBladeComponents(): void
    {
        Blade::component(Card::class, 'admin-card');
        Blade::component(DataTable::class, 'admin-data-table');
        Blade::component(FormGroup::class, 'admin-form-group');
        Blade::component(Modal::class, 'admin-modal');
        Blade::component(Alert::class, 'admin-alert');
        Blade::component(Toast::class, 'admin-toast');
        Blade::component(Pagination::class, 'admin-pagination');
    }

    private function registerPermissionGate(): void
    {
        Gate::before(function (object $user, string $ability): ?bool {
            if (! method_exists($user, 'hasPermission')) {
                return null;
            }

            return $user->hasPermission($ability) ? true : null;
        });
    }

    private function registerRouteMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('permission', CheckPermission::class);
    }

    private function registerCorePermissions(): void
    {
        $this->app->make(PermissionRegistry::class)->register([
            [
                'key' => 'users.index',
                'name' => 'View users',
                'group' => 'users',
                'description' => 'View admin user list.',
            ],
            [
                'key' => 'users.create',
                'name' => 'Create users',
                'group' => 'users',
                'description' => 'Create admin users.',
            ],
            [
                'key' => 'users.edit',
                'name' => 'Edit users',
                'group' => 'users',
                'description' => 'Edit admin users and account state.',
            ],
            [
                'key' => 'users.delete',
                'name' => 'Delete users',
                'group' => 'users',
                'description' => 'Delete admin users.',
            ],
            [
                'key' => 'roles.index',
                'name' => 'View roles',
                'group' => 'roles',
                'description' => 'View admin role list.',
            ],
            [
                'key' => 'roles.create',
                'name' => 'Create roles',
                'group' => 'roles',
                'description' => 'Create admin roles.',
            ],
            [
                'key' => 'roles.edit',
                'name' => 'Edit roles',
                'group' => 'roles',
                'description' => 'Edit admin roles and assigned permissions.',
            ],
            [
                'key' => 'roles.delete',
                'name' => 'Delete roles',
                'group' => 'roles',
                'description' => 'Delete admin roles.',
            ],
            [
                'key' => 'permissions.index',
                'name' => 'View permissions',
                'group' => 'permissions',
                'description' => 'View registered admin permissions.',
            ],
            [
                'key' => 'settings.edit',
                'name' => 'Edit settings',
                'group' => 'settings',
                'description' => 'Edit general site settings.',
            ],
        ], 'core/base');
    }

    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            SyncPermissionsCommand::class,
        ]);
    }

    private function registerCoreAdminMenu(): void
    {
        $this->app->make(AdminMenuRegistry::class)->register([
            [
                'id' => 'dashboard',
                'title' => 'Dashboard',
                'route' => 'admin.dashboard',
                'icon' => 'home',
                'active' => ['admin.dashboard'],
                'order' => 10,
            ],
            [
                'id' => 'access-control',
                'title' => 'Access Control',
                'icon' => 'users',
                'active' => ['admin.users.*', 'admin.roles.*', 'admin.permissions.*'],
                'order' => 20,
                'children' => [
                    [
                        'id' => 'users',
                        'title' => 'Users',
                        'route' => 'admin.users.index',
                        'permission' => 'users.index',
                        'active' => ['admin.users.*'],
                        'order' => 10,
                    ],
                    [
                        'id' => 'roles',
                        'title' => 'Roles',
                        'route' => 'admin.roles.index',
                        'permission' => 'roles.index',
                        'active' => ['admin.roles.*'],
                        'order' => 20,
                    ],
                    [
                        'id' => 'permissions',
                        'title' => 'Permissions',
                        'route' => 'admin.permissions.index',
                        'permission' => 'permissions.index',
                        'active' => ['admin.permissions.*'],
                        'order' => 30,
                    ],
                ],
            ],
            [
                'id' => 'settings',
                'title' => 'Settings',
                'route' => 'admin.settings.edit',
                'permission' => 'settings.edit',
                'icon' => 'settings',
                'active' => ['admin.settings.*'],
                'order' => 90,
            ],
        ]);
    }

    private function applyApplicationSettings(): void
    {
        $this->app->make(SettingStore::class)->applyApplicationConfig();
    }
}
