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
            [
                'key' => 'media.file.trash',
                'name' => 'Trash media files',
                'group' => 'media file',
                'description' => 'Move media files to trash.',
            ],
            [
                'key' => 'media.folder.create',
                'name' => 'Create media folders',
                'group' => 'media folder',
                'description' => 'Create folders in the media library.',
            ],
            [
                'key' => 'media.folder.edit',
                'name' => 'Edit media folders',
                'group' => 'media folder',
                'description' => 'Rename and move media folders.',
            ],
            [
                'key' => 'media.folder.trash',
                'name' => 'Trash media folders',
                'group' => 'media folder',
                'description' => 'Move media folders to trash.',
            ],
            [
                'key' => 'media.folder.delete',
                'name' => 'Delete media folders',
                'group' => 'media folder',
                'description' => 'Permanently delete media folders.',
            ],
            [
                'key' => 'static_blocks.create',
                'name' => 'Create static blocks',
                'group' => 'static blocks',
                'description' => 'Create reusable static content blocks.',
            ],
            [
                'key' => 'static_blocks.edit',
                'name' => 'Edit static blocks',
                'group' => 'static blocks',
                'description' => 'Edit reusable static content blocks.',
            ],
            [
                'key' => 'static_blocks.delete',
                'name' => 'Delete static blocks',
                'group' => 'static blocks',
                'description' => 'Delete reusable static content blocks.',
            ],
            [
                'key' => 'contact.edit',
                'name' => 'Edit contact messages',
                'group' => 'contact',
                'description' => 'View and update contact messages.',
            ],
            [
                'key' => 'contact.delete',
                'name' => 'Delete contact messages',
                'group' => 'contact',
                'description' => 'Delete contact messages.',
            ],
            [
                'key' => 'contact.custom_fields',
                'name' => 'Manage contact custom fields',
                'group' => 'contact',
                'description' => 'Manage custom fields on contact forms.',
            ],
            [
                'key' => 'custom_fields.create',
                'name' => 'Create custom fields',
                'group' => 'custom fields',
                'description' => 'Create custom field definitions.',
            ],
            [
                'key' => 'custom_fields.edit',
                'name' => 'Edit custom fields',
                'group' => 'custom fields',
                'description' => 'Edit custom field definitions.',
            ],
            [
                'key' => 'custom_fields.delete',
                'name' => 'Delete custom fields',
                'group' => 'custom fields',
                'description' => 'Delete custom field definitions.',
            ],
            [
                'key' => 'reports.index',
                'name' => 'View reports',
                'group' => 'reports',
                'description' => 'View content reports.',
            ],
            [
                'key' => 'galleries.create',
                'name' => 'Create galleries',
                'group' => 'galleries',
                'description' => 'Create image galleries.',
            ],
            [
                'key' => 'galleries.edit',
                'name' => 'Edit galleries',
                'group' => 'galleries',
                'description' => 'Edit image galleries.',
            ],
            [
                'key' => 'galleries.delete',
                'name' => 'Delete galleries',
                'group' => 'galleries',
                'description' => 'Delete image galleries.',
            ],
            [
                'key' => 'members.create',
                'name' => 'Create members',
                'group' => 'members',
                'description' => 'Create frontend members.',
            ],
            [
                'key' => 'members.edit',
                'name' => 'Edit members',
                'group' => 'members',
                'description' => 'Edit frontend members.',
            ],
            [
                'key' => 'members.delete',
                'name' => 'Delete members',
                'group' => 'members',
                'description' => 'Delete frontend members.',
            ],
            [
                'key' => 'settings.email',
                'name' => 'Edit email settings',
                'group' => 'settings common',
                'description' => 'Edit email settings.',
            ],
            [
                'key' => 'settings.media',
                'name' => 'Edit media settings',
                'group' => 'settings common',
                'description' => 'Edit media settings.',
            ],
            [
                'key' => 'settings.admin_appearance',
                'name' => 'Edit admin appearance',
                'group' => 'settings common',
                'description' => 'Edit admin appearance settings.',
            ],
            [
                'key' => 'settings.cache',
                'name' => 'Manage cache settings',
                'group' => 'settings common',
                'description' => 'Manage cache settings.',
            ],
            [
                'key' => 'settings.datatables',
                'name' => 'Edit datatables settings',
                'group' => 'settings common',
                'description' => 'Edit datatables settings.',
            ],
            [
                'key' => 'settings.email_rules',
                'name' => 'Edit email rules',
                'group' => 'settings common',
                'description' => 'Edit email rule settings.',
            ],
            [
                'key' => 'settings.phone_number',
                'name' => 'Edit phone number settings',
                'group' => 'settings common',
                'description' => 'Edit phone number settings.',
            ],
            [
                'key' => 'settings.optimize',
                'name' => 'Edit optimization settings',
                'group' => 'settings common',
                'description' => 'Edit site optimization settings.',
            ],
            [
                'key' => 'settings.website_tracking',
                'name' => 'Edit website tracking settings',
                'group' => 'settings common',
                'description' => 'Edit website tracking settings.',
            ],
            [
                'key' => 'settings.analytics',
                'name' => 'Edit analytics settings',
                'group' => 'settings others',
                'description' => 'Edit analytics integration settings.',
            ],
            [
                'key' => 'settings.blog',
                'name' => 'Edit blog settings',
                'group' => 'settings others',
                'description' => 'Edit blog settings.',
            ],
            [
                'key' => 'settings.captcha',
                'name' => 'Edit captcha settings',
                'group' => 'settings others',
                'description' => 'Edit captcha settings.',
            ],
            [
                'key' => 'settings.contact',
                'name' => 'Edit contact settings',
                'group' => 'settings others',
                'description' => 'Edit contact settings.',
            ],
            [
                'key' => 'settings.member',
                'name' => 'Edit member settings',
                'group' => 'settings others',
                'description' => 'Edit member settings.',
            ],
            [
                'key' => 'settings.social_login',
                'name' => 'Edit social login settings',
                'group' => 'settings others',
                'description' => 'Edit social login settings.',
            ],
            [
                'key' => 'settings.sitemap',
                'name' => 'Edit sitemap settings',
                'group' => 'settings sitemap',
                'description' => 'Edit sitemap settings.',
            ],
            [
                'key' => 'settings.languages.create',
                'name' => 'Create languages',
                'group' => 'settings languages',
                'description' => 'Create site languages.',
            ],
            [
                'key' => 'settings.languages.edit',
                'name' => 'Edit languages',
                'group' => 'settings languages',
                'description' => 'Edit site languages.',
            ],
            [
                'key' => 'settings.languages.delete',
                'name' => 'Delete languages',
                'group' => 'settings languages',
                'description' => 'Delete site languages.',
            ],
            [
                'key' => 'settings.localization.locales',
                'name' => 'Manage locales',
                'group' => 'settings localization',
                'description' => 'Manage localization locales.',
            ],
            [
                'key' => 'settings.localization.theme_translations',
                'name' => 'Manage theme translations',
                'group' => 'settings localization',
                'description' => 'Manage theme translation files.',
            ],
            [
                'key' => 'settings.localization.other_translations',
                'name' => 'Manage other translations',
                'group' => 'settings localization',
                'description' => 'Manage package and plugin translations.',
            ],
            [
                'key' => 'api.sanctum_tokens.create',
                'name' => 'Create Sanctum tokens',
                'group' => 'api sanctum tokens',
                'description' => 'Create API access tokens.',
            ],
            [
                'key' => 'api.sanctum_tokens.delete',
                'name' => 'Delete Sanctum tokens',
                'group' => 'api sanctum tokens',
                'description' => 'Delete API access tokens.',
            ],
            [
                'key' => 'cronjobs.manage',
                'name' => 'Manage cronjobs',
                'group' => 'cronjobs',
                'description' => 'Manage scheduled jobs.',
            ],
            [
                'key' => 'security.manage',
                'name' => 'Manage security settings',
                'group' => 'security',
                'description' => 'View and manage security settings.',
            ],
            [
                'key' => 'cleanup.manage',
                'name' => 'Manage cleanup system',
                'group' => 'cleanup',
                'description' => 'Clean database tables from the system cleanup tool.',
            ],
            [
                'key' => 'system.info',
                'name' => 'View system information',
                'group' => 'system information',
                'description' => 'View system environment and package information.',
            ],
            [
                'key' => 'system.updater',
                'name' => 'Manage system updater',
                'group' => 'system updater',
                'description' => 'Run system update checks and updater steps.',
            ],
            [
                'key' => 'license.manage',
                'name' => 'Manage license',
                'group' => 'license',
                'description' => 'Manage product license details.',
            ],
            [
                'key' => 'plugins.activate',
                'name' => 'Activate or deactivate plugins',
                'group' => 'plugins',
                'description' => 'Activate and deactivate plugins.',
            ],
            [
                'key' => 'plugins.remove',
                'name' => 'Remove plugins',
                'group' => 'plugins',
                'description' => 'Remove installed plugins.',
            ],
            [
                'key' => 'plugins.create',
                'name' => 'Add new plugins',
                'group' => 'plugins',
                'description' => 'Upload or install new plugins.',
            ],
            [
                'key' => 'appearance.theme.activate',
                'name' => 'Activate themes',
                'group' => 'appearance theme',
                'description' => 'Activate frontend themes.',
            ],
            [
                'key' => 'appearance.theme.remove',
                'name' => 'Remove themes',
                'group' => 'appearance theme',
                'description' => 'Remove frontend themes.',
            ],
            [
                'key' => 'appearance.theme_options',
                'name' => 'Edit theme options',
                'group' => 'appearance',
                'description' => 'Edit active theme options.',
            ],
            [
                'key' => 'appearance.custom_css',
                'name' => 'Edit custom CSS',
                'group' => 'appearance',
                'description' => 'Edit frontend custom CSS.',
            ],
            [
                'key' => 'appearance.custom_js',
                'name' => 'Edit custom JS',
                'group' => 'appearance',
                'description' => 'Edit frontend custom JavaScript.',
            ],
            [
                'key' => 'appearance.custom_html',
                'name' => 'Edit custom HTML',
                'group' => 'appearance',
                'description' => 'Edit custom HTML snippets.',
            ],
            [
                'key' => 'appearance.robots',
                'name' => 'Edit robots.txt',
                'group' => 'appearance',
                'description' => 'Edit robots.txt content.',
            ],
            [
                'key' => 'analytics.top_page',
                'name' => 'View top pages',
                'group' => 'analytics',
                'description' => 'View top page analytics.',
            ],
            [
                'key' => 'analytics.top_browser',
                'name' => 'View top browsers',
                'group' => 'analytics',
                'description' => 'View top browser analytics.',
            ],
            [
                'key' => 'analytics.top_referrer',
                'name' => 'View top referrers',
                'group' => 'analytics',
                'description' => 'View top referrer analytics.',
            ],
            [
                'key' => 'activity_logs.delete',
                'name' => 'Delete activity logs',
                'group' => 'activity logs',
                'description' => 'Delete activity log entries.',
            ],
            [
                'key' => 'request_logs.index',
                'name' => 'View request logs',
                'group' => 'request logs',
                'description' => 'View request log entries.',
            ],
            [
                'key' => 'request_logs.delete',
                'name' => 'Delete request logs',
                'group' => 'request logs',
                'description' => 'Delete request log entries.',
            ],
            [
                'key' => 'backups.create',
                'name' => 'Create backups',
                'group' => 'backups',
                'description' => 'Create backup archives.',
            ],
            [
                'key' => 'backups.restore',
                'name' => 'Restore backups',
                'group' => 'backups',
                'description' => 'Restore backup archives.',
            ],
            [
                'key' => 'backups.delete',
                'name' => 'Delete backups',
                'group' => 'backups',
                'description' => 'Delete backup archives.',
            ],
            [
                'key' => 'tools.export_pages',
                'name' => 'Export pages',
                'group' => 'import export data',
                'description' => 'Export page data.',
            ],
            [
                'key' => 'tools.import_pages',
                'name' => 'Import pages',
                'group' => 'import export data',
                'description' => 'Import page data.',
            ],
            [
                'key' => 'tools.export_posts',
                'name' => 'Export posts',
                'group' => 'import export data',
                'description' => 'Export post data.',
            ],
            [
                'key' => 'tools.import_posts',
                'name' => 'Import posts',
                'group' => 'import export data',
                'description' => 'Import post data.',
            ],
            [
                'key' => 'tools.import_translations',
                'name' => 'Import translations',
                'group' => 'import export data',
                'description' => 'Import translation files.',
            ],
            [
                'key' => 'tools.export_translations',
                'name' => 'Export translations',
                'group' => 'import export data',
                'description' => 'Export translation files.',
            ],
            [
                'key' => 'tools.import_property_translations',
                'name' => 'Import property translations',
                'group' => 'import export data',
                'description' => 'Import property translation files.',
            ],
            [
                'key' => 'tools.export_property_translations',
                'name' => 'Export property translations',
                'group' => 'import export data',
                'description' => 'Export property translation files.',
            ],
            [
                'key' => 'tools.export_page_translations',
                'name' => 'Export page translations',
                'group' => 'import export data',
                'description' => 'Export page translation files.',
            ],
            [
                'key' => 'tools.import_page_translations',
                'name' => 'Import page translations',
                'group' => 'import export data',
                'description' => 'Import page translation files.',
            ],
            [
                'key' => 'tools.export_theme_translations',
                'name' => 'Export theme translations',
                'group' => 'import export data',
                'description' => 'Export theme translation files.',
            ],
            [
                'key' => 'tools.export_other_translations',
                'name' => 'Export other translations',
                'group' => 'import export data',
                'description' => 'Export other translation files.',
            ],
            [
                'key' => 'tools.import_theme_translations',
                'name' => 'Import theme translations',
                'group' => 'import export data',
                'description' => 'Import theme translation files.',
            ],
            [
                'key' => 'tools.import_other_translations',
                'name' => 'Import other translations',
                'group' => 'import export data',
                'description' => 'Import other translation files.',
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
                'active' => ['admin.users.*', 'admin.permissions.*'],
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
