<?php

use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Http\Controllers\Admin\AuditLogController;
use Sitewyn\Core\Base\Http\Controllers\Admin\AuthController;
use Sitewyn\Core\Base\Http\Controllers\Admin\BackupController;
use Sitewyn\Core\Base\Http\Controllers\Admin\CacheController;
use Sitewyn\Core\Base\Http\Controllers\Admin\CleanupController;
use Sitewyn\Core\Base\Http\Controllers\Admin\CronjobController;
use Sitewyn\Core\Base\Http\Controllers\Admin\DashboardController;
use Sitewyn\Core\Base\Http\Controllers\Admin\LanguageController;
use Sitewyn\Core\Base\Http\Controllers\Admin\MenuController;
use Sitewyn\Core\Base\Http\Controllers\Admin\PasswordResetController;
use Sitewyn\Core\Base\Http\Controllers\Admin\PermissionController;
use Sitewyn\Core\Base\Http\Controllers\Admin\PlatformAdminController;
use Sitewyn\Core\Base\Http\Controllers\Admin\PluginManageController;
use Sitewyn\Core\Base\Http\Controllers\Admin\RequestLogController;
use Sitewyn\Core\Base\Http\Controllers\Admin\RoleController;
use Sitewyn\Core\Base\Http\Controllers\Admin\SearchController;
use Sitewyn\Core\Base\Http\Controllers\Admin\SecurityController;
use Sitewyn\Core\Base\Http\Controllers\Admin\SettingController;
use Sitewyn\Core\Base\Http\Controllers\Admin\SystemInfoController;
use Sitewyn\Core\Base\Http\Controllers\Admin\SystemUpdaterController;
use Sitewyn\Core\Base\Http\Controllers\Admin\SystemUserController;
use Sitewyn\Core\Base\Http\Controllers\Admin\UserController;
use Sitewyn\Core\Base\Http\Controllers\Admin\WidgetController;
use Sitewyn\Core\Base\Http\Controllers\RobotsController;
use Sitewyn\Core\Base\Http\Controllers\SitemapController;

Route::get('/_platform/core/base', static fn () => response()->json([
    'module' => 'core/base',
    'status' => 'ok',
]))->name('platform.core.base.health');

// Public SEO files, fetched by crawlers: no auth, no session middleware.
// Core routes load before every package route, so these are matched before
// the page catch-all /{slug} can swallow the single-segment file names.
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots.txt');

Route::prefix('admin')
    ->middleware('web')
    ->name('admin.')
    ->group(function (): void {
        Route::middleware('guest:admin')->group(function (): void {
            Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
            Route::post('login', [AuthController::class, 'login'])->name('login.store');
            Route::get('forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
            Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
            Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
            Route::post('reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
        });

        Route::middleware('auth:admin')->group(function (): void {
            Route::get('/', DashboardController::class)->name('dashboard');
            Route::get('search', SearchController::class)->name('search');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');

            // Platform Administration hub — no permission gate, every signed-in
            // admin can open it; each card on the page gates itself (Dashboard
            // precedent).
            Route::get('system', PlatformAdminController::class)->name('system');

            Route::get('users', [UserController::class, 'index'])
                ->middleware('permission:users.index')
                ->name('users.index');
            Route::get('users/create', [UserController::class, 'create'])
                ->middleware('permission:users.create')
                ->name('users.create');
            Route::post('users', [UserController::class, 'store'])
                ->middleware('permission:users.create')
                ->name('users.store');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])
                ->middleware('permission:users.edit')
                ->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])
                ->middleware('permission:users.edit')
                ->name('users.update');
            Route::delete('users/{user}', [UserController::class, 'destroy'])
                ->middleware('permission:users.delete')
                ->name('users.destroy');

            // Team user management lives inside Platform Administration
            // (/admin/system, hub card "Users"). These routes manage the
            // platform team — super admins and holders of the built-in Admin
            // role — while /admin/users manages everyone else. Escalation
            // guards (self-edit strip, super-only flag, role subset rule)
            // live in SystemUserController.
            Route::get('system/users', [SystemUserController::class, 'index'])
                ->middleware('permission:system.users.index')
                ->name('system.users.index');
            Route::get('system/users/create', [SystemUserController::class, 'create'])
                ->middleware('permission:system.users.create')
                ->name('system.users.create');
            Route::post('system/users', [SystemUserController::class, 'store'])
                ->middleware('permission:system.users.create')
                ->name('system.users.store');
            Route::get('system/users/{user}/edit', [SystemUserController::class, 'edit'])
                ->middleware('permission:system.users.edit')
                ->name('system.users.edit');
            Route::put('system/users/{user}', [SystemUserController::class, 'update'])
                ->middleware('permission:system.users.edit')
                ->name('system.users.update');
            Route::delete('system/users/{user}', [SystemUserController::class, 'destroy'])
                ->middleware('permission:system.users.delete')
                ->name('system.users.destroy');

            // Role management also lives inside Platform Administration
            // (/admin/system, hub card "Roles & Permissions"), mirroring the
            // system/users split. Permission keys stay roles.* — renaming
            // them would orphan role_permission pivots for existing roles.
            Route::get('system/roles', [RoleController::class, 'index'])
                ->middleware('permission:roles.index')
                ->name('system.roles.index');
            Route::get('system/roles/create', [RoleController::class, 'create'])
                ->middleware('permission:roles.create')
                ->name('system.roles.create');
            Route::post('system/roles', [RoleController::class, 'store'])
                ->middleware('permission:roles.create')
                ->name('system.roles.store');
            Route::get('system/roles/{role}/edit', [RoleController::class, 'edit'])
                ->middleware('permission:roles.edit')
                ->name('system.roles.edit');
            Route::put('system/roles/{role}', [RoleController::class, 'update'])
                ->middleware('permission:roles.edit')
                ->name('system.roles.update');
            Route::delete('system/roles/{role}', [RoleController::class, 'destroy'])
                ->middleware('permission:roles.delete')
                ->name('system.roles.destroy');

            Route::get('permissions', [PermissionController::class, 'index'])
                ->middleware('permission:permissions.index')
                ->name('permissions.index');

            Route::get('settings', [SettingController::class, 'edit'])
                ->middleware('permission:settings.edit')
                ->name('settings.edit');
            Route::get('settings/general', [SettingController::class, 'general'])
                ->middleware('permission:settings.edit')
                ->name('settings.general');
            Route::get('settings/email', [SettingController::class, 'email'])
                ->middleware('permission:settings.email')
                ->name('settings.email');
            Route::put('settings/email', [SettingController::class, 'updateEmail'])
                ->middleware('permission:settings.email')
                ->name('settings.email.update');
            Route::post('settings/email/test', [SettingController::class, 'sendTestEmail'])
                ->middleware('permission:settings.email')
                ->name('settings.email.test');
            Route::get('settings/email/templates', [SettingController::class, 'emailTemplates'])
                ->middleware('permission:settings.email')
                ->name('settings.email.templates');
            Route::put('settings/email/templates', [SettingController::class, 'updateEmailTemplates'])
                ->middleware('permission:settings.email')
                ->name('settings.email.templates.update');
            Route::get('settings/email/rules', [SettingController::class, 'emailRules'])
                ->middleware('permission:settings.email_rules')
                ->name('settings.email.rules');
            Route::put('settings/email/rules', [SettingController::class, 'updateEmailRules'])
                ->middleware('permission:settings.email_rules')
                ->name('settings.email.rules.update');
            Route::get('settings/phone-number', [SettingController::class, 'phoneNumber'])
                ->middleware('permission:settings.phone_number')
                ->name('settings.phone-number');
            Route::put('settings/phone-number', [SettingController::class, 'updatePhoneNumber'])
                ->middleware('permission:settings.phone_number')
                ->name('settings.phone-number.update');
            Route::get('settings/media', [SettingController::class, 'media'])
                ->middleware('permission:settings.media')
                ->name('settings.media');
            Route::put('settings/media', [SettingController::class, 'updateMedia'])
                ->middleware('permission:settings.media')
                ->name('settings.media.update');
            Route::post('settings/media/generate-thumbnails', [SettingController::class, 'generateMediaThumbnails'])
                ->middleware('permission:settings.media')
                ->name('settings.media.generate-thumbnails');
            Route::get('settings/permalink', [SettingController::class, 'permalink'])
                ->middleware('permission:settings.permalink')
                ->name('settings.permalink');
            Route::put('settings/permalink', [SettingController::class, 'updatePermalink'])
                ->middleware('permission:settings.permalink')
                ->name('settings.permalink.update');
            Route::put('settings', [SettingController::class, 'update'])
                ->middleware('permission:settings.edit')
                ->name('settings.update');

            // Language management is part of the Settings area and reuses the
            // settings.edit permission — deliberately no new permission key.
            Route::get('settings/languages', [LanguageController::class, 'index'])
                ->middleware('permission:settings.edit')
                ->name('settings.languages.index');
            Route::post('settings/languages', [LanguageController::class, 'store'])
                ->middleware('permission:settings.edit')
                ->name('settings.languages.store');
            Route::put('settings/languages/settings', [LanguageController::class, 'updateSettings'])
                ->middleware('permission:settings.edit')
                ->name('settings.languages.settings.update');
            Route::put('settings/languages/{language}', [LanguageController::class, 'update'])
                ->middleware('permission:settings.edit')
                ->name('settings.languages.update');
            Route::post('settings/languages/{language}/delete', [LanguageController::class, 'destroy'])
                ->middleware('permission:settings.edit')
                ->name('settings.languages.destroy');
            Route::post('settings/languages/{language}/make-default', [LanguageController::class, 'makeDefault'])
                ->middleware('permission:settings.edit')
                ->name('settings.languages.make-default');

            Route::get('plugins', [PluginManageController::class, 'index'])
                ->middleware('permission:plugins.manage')
                ->name('plugins.index');
            Route::post('plugins/{slug}/activate', [PluginManageController::class, 'activate'])
                ->middleware('permission:plugins.manage')
                ->name('plugins.activate');
            Route::post('plugins/{slug}/deactivate', [PluginManageController::class, 'deactivate'])
                ->middleware('permission:plugins.manage')
                ->name('plugins.deactivate');

            Route::get('audit-logs', [AuditLogController::class, 'index'])
                ->middleware('permission:audit.index')
                ->name('audit-logs.index');

            Route::get('request-logs', [RequestLogController::class, 'index'])
                ->middleware('permission:request_logs.index')
                ->name('request-logs.index');
            Route::delete('request-logs/bulk-destroy', [RequestLogController::class, 'bulkDestroy'])
                ->middleware('permission:request_logs.delete')
                ->name('request-logs.bulk-destroy');
            Route::delete('request-logs/clear', [RequestLogController::class, 'clear'])
                ->middleware('permission:request_logs.delete')
                ->name('request-logs.clear');
            Route::delete('request-logs/{requestLog}', [RequestLogController::class, 'destroy'])
                ->middleware('permission:request_logs.delete')
                ->name('request-logs.destroy');

            Route::get('system/backups', [BackupController::class, 'index'])
                ->middleware('permission:backups.manage')
                ->name('system.backups.index');
            Route::post('system/backups', [BackupController::class, 'create'])
                ->middleware('permission:backups.manage')
                ->name('system.backups.create');
            Route::get('system/backups/{name}/download', [BackupController::class, 'download'])
                ->middleware('permission:backups.manage')
                ->name('system.backups.download');
            Route::get('system/backups/{name}/download-database', [BackupController::class, 'downloadDatabase'])
                ->middleware('permission:backups.manage')
                ->name('system.backups.download-database');
            Route::get('system/backups/{name}/download-uploads', [BackupController::class, 'downloadUploads'])
                ->middleware('permission:backups.manage')
                ->name('system.backups.download-uploads');
            Route::post('system/backups/{name}/restore', [BackupController::class, 'restore'])
                ->middleware('permission:backups.manage')
                ->name('system.backups.restore');
            Route::post('system/backups/{name}/delete', [BackupController::class, 'delete'])
                ->middleware('permission:backups.manage')
                ->name('system.backups.delete');

            Route::get('system/cronjob', CronjobController::class)
                ->middleware('permission:cronjobs.manage')
                ->name('system.cronjob');

            Route::get('system/security', SecurityController::class)
                ->middleware('permission:security.manage')
                ->name('system.security');

            Route::get('system/cache', [CacheController::class, 'index'])
                ->middleware('permission:settings.cache')
                ->name('system.cache.index');
            Route::post('system/cache/{operation}', [CacheController::class, 'run'])
                ->middleware('permission:settings.cache')
                ->whereIn('operation', ['clear-cms', 'refresh-views', 'clear-config', 'clear-routes', 'clear-logs', 'optimize', 'clear-optimization'])
                ->name('system.cache.run');

            Route::get('system/cleanup', [CleanupController::class, 'index'])
                ->middleware('permission:cleanup.manage')
                ->name('system.cleanup.index');
            Route::post('system/cleanup', [CleanupController::class, 'cleanup'])
                ->middleware('permission:cleanup.manage')
                ->name('system.cleanup.run');

            Route::get('system/info', SystemInfoController::class)
                ->middleware('permission:system.info')
                ->name('system.info');

            Route::get('system/updater', [SystemUpdaterController::class, 'index'])
                ->middleware('permission:system.updater')
                ->name('system.updater.index');
            Route::post('system/updater/reinstall', [SystemUpdaterController::class, 'reinstall'])
                ->middleware('permission:system.updater')
                ->name('system.updater.reinstall');
            Route::post('system/updater/steps/{step}', [SystemUpdaterController::class, 'runStep'])
                ->middleware('permission:system.updater')
                ->whereNumber('step')
                ->name('system.updater.steps.run');

            Route::get('menus', [MenuController::class, 'index'])
                ->middleware('permission:menus.manage')
                ->name('menus.index');
            Route::get('menus/create', [MenuController::class, 'create'])
                ->middleware('permission:menus.manage')
                ->name('menus.create');
            Route::post('menus', [MenuController::class, 'store'])
                ->middleware('permission:menus.manage')
                ->name('menus.store');
            Route::get('menus/{menu}/edit-items', [MenuController::class, 'editItems'])
                ->middleware('permission:menus.manage')
                ->name('menus.edit-items');
            Route::post('menus/{menu}/items', [MenuController::class, 'storeItems'])
                ->middleware('permission:menus.manage')
                ->name('menus.store-items');
            Route::get('menus/{menu}/edit', [MenuController::class, 'edit'])
                ->middleware('permission:menus.manage')
                ->name('menus.edit');
            Route::put('menus/{menu}', [MenuController::class, 'update'])
                ->middleware('permission:menus.manage')
                ->name('menus.update');
            Route::delete('menus/{menu}', [MenuController::class, 'destroy'])
                ->middleware('permission:menus.manage')
                ->name('menus.destroy');

            Route::get('widgets', [WidgetController::class, 'index'])
                ->middleware('permission:widgets.manage')
                ->name('widgets.index');
            Route::get('widgets/create', [WidgetController::class, 'create'])
                ->middleware('permission:widgets.manage')
                ->name('widgets.create');
            Route::post('widgets', [WidgetController::class, 'store'])
                ->middleware('permission:widgets.manage')
                ->name('widgets.store');
            Route::get('widgets/{widget}/edit', [WidgetController::class, 'edit'])
                ->middleware('permission:widgets.manage')
                ->name('widgets.edit');
            Route::put('widgets/{widget}', [WidgetController::class, 'update'])
                ->middleware('permission:widgets.manage')
                ->name('widgets.update');
            Route::post('widgets/{widget}/move', [WidgetController::class, 'move'])
                ->middleware('permission:widgets.manage')
                ->name('widgets.move');
            Route::delete('widgets/{widget}', [WidgetController::class, 'destroy'])
                ->middleware('permission:widgets.manage')
                ->name('widgets.destroy');
        });
    });
