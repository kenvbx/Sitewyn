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
        $this->assertTrue($registry->has('roles.index'));
        $this->assertTrue($registry->has('roles.create'));
        $this->assertTrue($registry->has('roles.edit'));
        $this->assertTrue($registry->has('roles.delete'));
        $this->assertTrue($registry->has('permissions.index'));
        $this->assertTrue($registry->has('settings.edit'));
        $this->assertTrue($registry->has('media.index'));
        $this->assertTrue($registry->has('media.upload'));
        $this->assertTrue($registry->has('media.edit'));
        $this->assertTrue($registry->has('media.delete'));
        $this->assertSame(14, $registry->all()->count());
        $this->assertSame(['media', 'permissions', 'roles', 'settings', 'users'], $registry->grouped()->keys()->sort()->values()->all());
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

        $this->artisan('permission:sync')
            ->expectsOutputToContain('Synced 14 permissions.')
            ->assertSuccessful();

        $this->assertDatabaseCount('permissions', 14);
        $this->assertDatabaseHas('permissions', [
            'name' => 'View users',
            'key' => 'users.index',
            'module' => 'core/base',
            'group' => 'users',
            'description' => 'View admin user list.',
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
            'key' => 'media.index',
            'module' => 'package/media',
            'group' => 'media',
        ]);
    }
}
