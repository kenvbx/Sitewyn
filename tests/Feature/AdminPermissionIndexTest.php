<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Tests\TestCase;

class AdminPermissionIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_index_requires_permission(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->get('/admin/permissions')
            ->assertForbidden();
    }

    public function test_super_admin_can_view_registered_permissions(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->get('/admin/permissions')
            ->assertOk()
            ->assertSee('Permissions')
            ->assertSee('core/base')
            ->assertSee('users.index')
            ->assertSee('roles.delete')
            ->assertSee('permissions.index');

        $this->assertDatabaseHas('permissions', [
            'key' => 'permissions.index',
            'module' => 'core/base',
            'group' => 'permissions',
        ]);
    }

    public function test_admin_with_permissions_index_can_view_permissions(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);
        $role = Role::factory()->create();
        $permission = Permission::factory()->create([
            'key' => 'permissions.index',
            'module' => 'core/base',
            'group' => 'permissions',
        ]);

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->actingAs($user, 'admin')
            ->get('/admin/permissions')
            ->assertOk()
            ->assertSee('Permissions')
            ->assertSee('permissions.index');
    }
}
