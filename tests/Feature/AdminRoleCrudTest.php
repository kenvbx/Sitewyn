<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Tests\TestCase;

class AdminRoleCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_index_requires_permission(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->get('/admin/roles')
            ->assertForbidden();
    }

    public function test_super_admin_can_view_roles_index(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        Role::factory()->create([
            'name' => 'Editor',
            'slug' => 'editor',
        ]);

        $this->actingAs($user, 'admin')
            ->get('/admin/roles')
            ->assertOk()
            ->assertSee('Roles')
            ->assertSee('Editor');
    }

    public function test_super_admin_can_create_role_with_permissions(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->post('/admin/roles', [
                'name' => 'Content Editor',
                'permissions' => ['users.index', 'roles.index'],
            ])
            ->assertRedirect('/admin/roles');

        $role = Role::query()->where('slug', 'content-editor')->firstOrFail();

        $this->assertDatabaseHas('permissions', [
            'key' => 'users.index',
            'module' => 'core/base',
        ]);
        $this->assertSame(['roles.index', 'users.index'], $role->permissions()->pluck('key')->sort()->values()->all());
    }

    public function test_super_admin_can_update_role_permissions(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $role = Role::factory()->create([
            'name' => 'Old name',
            'slug' => 'old-name',
        ]);

        $this->actingAs($user, 'admin')
            ->put("/admin/roles/{$role->id}", [
                'name' => 'Role Manager',
                'slug' => 'role-manager',
                'description' => 'Manages admin roles.',
                'permissions' => ['roles.create', 'roles.edit'],
            ])
            ->assertRedirect("/admin/roles/{$role->id}/edit");

        $role->refresh();

        $this->assertSame('Role Manager', $role->name);
        $this->assertSame('role-manager', $role->slug);
        $this->assertSame('Manages admin roles.', $role->description);
        $this->assertSame(['roles.create', 'roles.edit'], $role->permissions()->pluck('key')->sort()->values()->all());
    }

    public function test_role_with_users_cannot_be_deleted(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $assignedUser = User::factory()->create();
        $role = Role::factory()->create();

        $assignedUser->roles()->attach($role);

        $this->actingAs($user, 'admin')
            ->delete("/admin/roles/{$role->id}")
            ->assertRedirect('/admin/roles')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
        ]);
    }

    public function test_custom_role_without_users_can_be_deleted(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $role = Role::factory()->create([
            'is_system' => false,
        ]);
        $permission = Permission::factory()->create();

        $role->permissions()->attach($permission);

        $this->actingAs($user, 'admin')
            ->delete("/admin/roles/{$role->id}")
            ->assertRedirect('/admin/roles')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
        $this->assertDatabaseMissing('permission_role', [
            'role_id' => $role->id,
        ]);
    }
}
