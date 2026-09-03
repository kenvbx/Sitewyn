<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Support\PermissionRegistry;
use Tests\TestCase;

class PermissionRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_base_registers_default_admin_permissions(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);

        $this->assertTrue($registry->has('users.index'));
        $this->assertTrue($registry->has('users.create'));
        $this->assertTrue($registry->has('users.edit'));
        $this->assertTrue($registry->has('users.delete'));
        $this->assertTrue($registry->has('system.users.index'));
        $this->assertTrue($registry->has('system.users.create'));
        $this->assertTrue($registry->has('system.users.edit'));
        $this->assertTrue($registry->has('system.users.delete'));
        $this->assertTrue($registry->has('roles.index'));
        $this->assertTrue($registry->has('roles.create'));
        $this->assertTrue($registry->has('roles.edit'));
        $this->assertTrue($registry->has('roles.delete'));
        $this->assertTrue($registry->has('permissions.index'));
        $this->assertTrue($registry->has('settings.edit'));
        $this->assertTrue($registry->has('plugins.manage'));
        $this->assertTrue($registry->has('audit.index'));
        $this->assertTrue($registry->has('backups.manage'));
        $this->assertTrue($registry->has('media.index'));
        $this->assertTrue($registry->has('media.upload'));
        $this->assertTrue($registry->has('media.edit'));
        $this->assertTrue($registry->has('media.delete'));
        $this->assertTrue($registry->has('page.index'));
        $this->assertTrue($registry->has('page.create'));
        $this->assertTrue($registry->has('page.edit'));
        $this->assertTrue($registry->has('page.delete'));
        $this->assertTrue($registry->has('post.index'));
        $this->assertTrue($registry->has('post.create'));
        $this->assertTrue($registry->has('post.edit'));
        $this->assertTrue($registry->has('post.delete'));
        $this->assertTrue($registry->has('category.index'));
        $this->assertTrue($registry->has('category.create'));
        $this->assertTrue($registry->has('category.edit'));
        $this->assertTrue($registry->has('category.delete'));
        $this->assertTrue($registry->has('tag.index'));
        $this->assertTrue($registry->has('tag.create'));
        $this->assertTrue($registry->has('tag.edit'));
        $this->assertTrue($registry->has('tag.delete'));
        $this->assertTrue($registry->has('menus.manage'));
        $this->assertTrue($registry->has('widgets.manage'));
        $this->assertTrue($registry->has('media.folder.create'));
        $this->assertTrue($registry->has('static_blocks.create'));
        $this->assertTrue($registry->has('contact.custom_fields'));
        $this->assertTrue($registry->has('custom_fields.edit'));
        $this->assertTrue($registry->has('galleries.create'));
        $this->assertTrue($registry->has('members.edit'));
        $this->assertTrue($registry->has('settings.email'));
        $this->assertTrue($registry->has('settings.media'));
        $this->assertTrue($registry->has('settings.permalink'));
        $this->assertTrue($registry->has('settings.phone_number'));
        $this->assertTrue($registry->has('settings.website_tracking'));
        $this->assertTrue($registry->has('settings.localization.theme_translations'));
        $this->assertTrue($registry->has('api.sanctum_tokens.create'));
        $this->assertTrue($registry->has('cronjobs.manage'));
        $this->assertTrue($registry->has('security.manage'));
        $this->assertTrue($registry->has('cleanup.manage'));
        $this->assertTrue($registry->has('system.info'));
        $this->assertTrue($registry->has('system.updater'));
        $this->assertTrue($registry->has('license.manage'));
        $this->assertTrue($registry->has('plugins.activate'));
        $this->assertTrue($registry->has('appearance.theme_options'));
        $this->assertTrue($registry->has('analytics.top_referrer'));
        $this->assertTrue($registry->has('request_logs.index'));
        $this->assertTrue($registry->has('request_logs.delete'));
        $this->assertTrue($registry->has('tools.import_other_translations'));
        $this->assertSame(124, $registry->all()->count());
        $this->assertContains('settings common', $registry->grouped()->keys());
        $this->assertContains('import export data', $registry->grouped()->keys());
    }

    public function test_permission_sync_command_persists_registered_permissions(): void
    {
        Permission::factory()->create([
            'name' => 'Old users label',
            'key' => 'users.index',
            'module' => 'legacy',
            'group' => 'legacy',
            'description' => 'Old description.',
        ]);

        $permissionCount = $this->app->make(PermissionRegistry::class)->all()->count();

        $this->artisan('permission:sync')
            ->expectsOutputToContain("Synced {$permissionCount} permissions.")
            ->assertSuccessful();

        $this->assertDatabaseCount('permissions', $permissionCount);
        $this->assertDatabaseHas('permissions', [
            'name' => 'View users',
            'key' => 'users.index',
            'module' => 'core/base',
            'group' => 'users',
            'description' => 'View admin user list.',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => 'View team users',
            'key' => 'system.users.index',
            'module' => 'core/base',
            'group' => 'system users',
            'description' => 'View the platform team user list.',
        ]);
        $this->assertDatabaseHas('permissions', [
            'key' => 'roles.delete',
            'module' => 'core/base',
            'group' => 'roles',
        ]);
        $this->assertDatabaseHas('permissions', [
            'key' => 'settings.edit',
            'module' => 'core/base',
            'group' => 'settings',
        ]);
        $this->assertDatabaseHas('permissions', [
            'key' => 'plugins.manage',
            'module' => 'core/base',
            'group' => 'plugins',
        ]);
        $this->assertDatabaseHas('permissions', [
            'key' => 'audit.index',
            'module' => 'core/base',
            'group' => 'audit',
        ]);
        $this->assertDatabaseHas('permissions', [
            'key' => 'backups.manage',
            'module' => 'core/base',
            'group' => 'backups',
        ]);
        $this->assertDatabaseHas('permissions', [
            'key' => 'media.index',
            'module' => 'package/media',
            'group' => 'media',
        ]);
        $this->assertDatabaseHas('permissions', [
            'key' => 'page.index',
            'module' => 'package/page',
            'group' => 'page',
        ]);
        $this->assertDatabaseHas('permissions', [
            'key' => 'post.index',
            'module' => 'package/blog',
            'group' => 'post',
        ]);
        $this->assertDatabaseHas('permissions', [
            'key' => 'category.index',
            'module' => 'package/blog',
            'group' => 'category',
        ]);
        $this->assertDatabaseHas('permissions', [
            'key' => 'tag.index',
            'module' => 'package/blog',
            'group' => 'tag',
        ]);
        $this->assertDatabaseHas('permissions', [
            'key' => 'settings.email',
            'module' => 'core/base',
            'group' => 'settings common',
        ]);
        $this->assertDatabaseHas('permissions', [
            'key' => 'appearance.theme_options',
            'module' => 'core/base',
            'group' => 'appearance',
        ]);
        $this->assertDatabaseHas('permissions', [
            'key' => 'request_logs.index',
            'module' => 'core/base',
            'group' => 'request logs',
        ]);
        $this->assertDatabaseHas('permissions', [
            'key' => 'tools.export_pages',
            'module' => 'core/base',
            'group' => 'import export data',
        ]);
    }
}
