<?php

namespace Sitewyn\Core\Base\Providers;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Sitewyn\Core\Base\Console\Commands\PluginActivateCommand;
use Sitewyn\Core\Base\Console\Commands\PluginDeactivateCommand;
use Sitewyn\Core\Base\Console\Commands\PluginListCommand;
use Sitewyn\Core\Base\Console\Commands\SyncPermissionsCommand;
use Sitewyn\Core\Base\Http\Middleware\CheckPermission;
use Sitewyn\Core\Base\Support\AdminFlash;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Sitewyn\Core\Base\Support\AuditLogger;
use Sitewyn\Core\Base\Support\BackupService;
use Sitewyn\Core\Base\Support\ModuleProviderRepository;
use Sitewyn\Core\Base\Support\PermissionRegistry;
use Sitewyn\Core\Base\Support\PluginActivator;
use Sitewyn\Core\Base\Support\PluginManager;
use Sitewyn\Core\Base\Support\SettingStore;
use Sitewyn\Core\Base\Support\SitemapRegistry;
use Sitewyn\Core\Base\Support\ThemeManager;
use Sitewyn\Core\Base\Support\WidgetRenderer;
use Sitewyn\Core\Base\View\Components\Admin\Alert;
use Sitewyn\Core\Base\View\Components\Admin\Card;
use Sitewyn\Core\Base\View\Components\Admin\DataTable;
use Sitewyn\Core\Base\View\Components\Admin\Editor;
use Sitewyn\Core\Base\View\Components\Admin\FormGroup;
use Sitewyn\Core\Base\View\Components\Admin\Modal;
use Sitewyn\Core\Base\View\Components\Admin\Pagination;
use Sitewyn\Core\Base\View\Components\Admin\Toast;
use Sitewyn\Core\Base\View\Components\WidgetArea;

class BaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/sitewyn-base.php'), 'sitewyn-base');
        $this->app->singleton(AdminFlash::class);
        $this->app->singleton(AdminMenuRegistry::class);
        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(BackupService::class);
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(PluginActivator::class);
        $this->app->singleton(PluginManager::class);
        $this->app->singleton(SettingStore::class);
        $this->app->singleton(SitemapRegistry::class);
        $this->app->singleton(ThemeManager::class);
        $this->app->singleton(WidgetRenderer::class);
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
        $this->registerAuthAuditListeners();
        $this->applyApplicationSettings();
        $this->registerActiveThemeViews();
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
        $pluginManager = $this->app->make(PluginManager::class);
        $excludedProviders = config('sitewyn-base.modules.excluded_providers', []);

        foreach ($repository->providerEntries(config('sitewyn-base.modules.provider_roots', [])) as $entry) {
            // Modules shipping a plugin.json manifest are gated on the
            // plugin's active state; manifest-less modules keep registering
            // unconditionally (no row in the plugins table counts as active).
            if ($entry['slug'] !== null && ! $pluginManager->isActive($entry['slug'])) {
                continue;
            }

            $provider = $entry['provider'];

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
        Blade::component(Editor::class, 'admin-editor');
        Blade::component(Modal::class, 'admin-modal');
        Blade::component(Alert::class, 'admin-alert');
        Blade::component(Toast::class, 'admin-toast');
        Blade::component(Pagination::class, 'admin-pagination');
        Blade::component(WidgetArea::class, 'widget-area');
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
                'key' => 'system.users.index',
                'name' => 'View team users',
                'group' => 'system users',
                'description' => 'View the platform team user list.',
            ],
            [
                'key' => 'system.users.create',
                'name' => 'Create team users',
                'group' => 'system users',
                'description' => 'Create platform team users.',
            ],
            [
                'key' => 'system.users.edit',
                'name' => 'Edit team users',
                'group' => 'system users',
                'description' => 'Edit platform team users and account state.',
            ],
            [
                'key' => 'system.users.delete',
                'name' => 'Delete team users',
                'group' => 'system users',
                'description' => 'Delete platform team users.',
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
            [
                'key' => 'plugins.manage',
                'name' => 'Manage plugins',
                'group' => 'plugins',
                'description' => 'Activate and deactivate plugins.',
            ],
            [
                'key' => 'audit.index',
                'name' => 'View audit logs',
                'group' => 'audit',
                'description' => 'Review the audit log of important admin actions.',
            ],
            [
                'key' => 'backups.manage',
                'name' => 'Manage backups',
                'group' => 'backups',
                'description' => 'Create, download, restore, and delete backups.',
            ],
            [
                'key' => 'menus.manage',
                'name' => 'Manage menus',
                'group' => 'menus',
                'description' => 'Create frontend navigation menus and organize their items.',
            ],
            [
                'key' => 'widgets.manage',
                'name' => 'Manage widgets',
                'group' => 'widgets',
                'description' => 'Manage the widget areas declared by the active theme.',
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
            PluginListCommand::class,
            PluginActivateCommand::class,
            PluginDeactivateCommand::class,
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
                'id' => 'menus',
                'title' => 'Menus',
                'route' => 'admin.menus.index',
                'permission' => 'menus.manage',
                'icon' => 'menu',
                'active' => ['admin.menus.*'],
                'order' => 25,
            ],
            [
                'id' => 'widgets',
                'title' => 'Widgets',
                'route' => 'admin.widgets.index',
                'permission' => 'widgets.manage',
                'icon' => 'widget',
                'active' => ['admin.widgets.*'],
                'order' => 26,
            ],
            [
                'id' => 'plugins',
                'title' => 'Plugins',
                'route' => 'admin.plugins.index',
                'permission' => 'plugins.manage',
                'icon' => 'plugin',
                'active' => ['admin.plugins.*'],
                'order' => 85,
            ],
            [
                'id' => 'audit-logs',
                'title' => 'Audit Logs',
                'route' => 'admin.audit-logs.index',
                'permission' => 'audit.index',
                'icon' => 'audit',
                'active' => ['admin.audit-logs.*'],
                'order' => 86,
            ],
            [
                'id' => 'backups',
                'title' => 'Backups',
                'route' => 'admin.backups.index',
                'permission' => 'backups.manage',
                'icon' => 'backup',
                'active' => ['admin.backups.*'],
                'order' => 87,
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
            [
                'id' => 'system',
                'title' => 'Platform Administration',
                'route' => 'admin.system',
                'icon' => 'shield',
                'active' => ['admin.system', 'admin.system.users.*'],
                'order' => 99,
            ],
        ]);
    }

    private function applyApplicationSettings(): void
    {
        $this->app->make(SettingStore::class)->applyApplicationConfig();
    }

    /**
     * Theme views (P5-02) are looked up first: prepending the active theme's
     * view directory makes top-level names like frontend.page resolve into
     * the theme, while everything else falls through to the app as before.
     * Runs after applyApplicationSettings() so the active_theme setting is
     * already readable. ThemeManager falls back to the default theme when
     * the setting is missing or stale, and this method skips cleanly when
     * even that is undiscoverable.
     */
    private function registerActiveThemeViews(): void
    {
        $theme = $this->app->make(ThemeManager::class)->activeTheme();

        if ($theme === []) {
            return;
        }

        $viewPath = $theme['path'].DIRECTORY_SEPARATOR.'resources/views';

        if (is_dir($viewPath)) {
            view()->getFinder()->prependLocation($viewPath);
        }
    }

    /**
     * Audit log entries for admin authentication: successful logins, logouts,
     * and failed credential attempts. The login flow in AuthController checks
     * credentials manually, so it fires the Failed event itself.
     */
    private function registerAuthAuditListeners(): void
    {
        $logger = fn (): AuditLogger => $this->app->make(AuditLogger::class);

        Event::listen(Login::class, function (Login $event) use ($logger): void {
            if ($event->guard === 'admin') {
                $logger()->record('login', $event->user->getMorphClass(), (int) $event->user->getAuthIdentifier());
            }
        });

        Event::listen(Logout::class, function (Logout $event) use ($logger): void {
            if ($event->guard === 'admin' && $event->user !== null) {
                $logger()->record('logout', $event->user->getMorphClass(), (int) $event->user->getAuthIdentifier());
            }
        });

        Event::listen(Failed::class, function (Failed $event) use ($logger): void {
            if ($event->guard !== 'admin') {
                return;
            }

            $logger()->record('login-failed', $event->user?->getMorphClass() ?? User::class, null, [
                'email' => $event->credentials['email'] ?? null,
            ]);
        });
    }
}
