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
            ->get('/admin/system/roles')
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
            ->get('/admin/system/roles')
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

        // Default "Save" stays on the saved role: create now redirects to the
        // new role's edit page ("Save and close" returns to the index instead).
        $response = $this->actingAs($user, 'admin')
            ->post('/admin/system/roles', [
                'name' => 'Content Editor',
                'permissions' => ['users.index', 'roles.index'],
            ]);

        $role = Role::query()->where('slug', 'content-editor')->firstOrFail();

        $response->assertRedirect("/admin/system/roles/{$role->id}/edit");

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
            ->put("/admin/system/roles/{$role->id}", [
                'name' => 'Role Manager',
                'slug' => 'role-manager',
                'description' => 'Manages admin roles.',
                'permissions' => ['roles.create', 'roles.edit'],
            ])
            ->assertRedirect("/admin/system/roles/{$role->id}/edit");

        $role->refresh();

        $this->assertSame('Role Manager', $role->name);
        $this->assertSame('role-manager', $role->slug);
        $this->assertSame('Manages admin roles.', $role->description);
        $this->assertSame(['roles.create', 'roles.edit'], $role->permissions()->pluck('key')->sort()->values()->all());
    }

    public function test_role_create_form_renders_permission_flags_tree(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->get('/admin/system/roles/create')
            ->assertOk()
            ->assertSee('Permission Flags')
            ->assertSee('All Permissions')
            ->assertSee('Collapse all')
            ->assertSee('Expand all')
            ->assertSee('data-role-all-master', false)
            // One Tabler card per module: collapsible body, 2-column groups.
            ->assertSee('data-module-card', false)
            ->assertSee('data-module-body', false)
            ->assertSee('data-module-master', false)
            ->assertSee('data-module-collapse', false)
            ->assertSee('data-group-block', false)
            ->assertSee('data-group-master', false)
            ->assertSee('data-perm-item', false)
            ->assertSee('data-role-permission', false)
            ->assertSee('permissions</span>', false)
            // Module badge (green); feature groups use a bold label, no orange badge.
            ->assertSee('badge bg-green-lt', false)
            ->assertSee('<span class="fw-bold">Users</span>', false)
            ->assertDontSee('badge bg-orange-lt', false)
            ->assertSee('>Core</span>', false)
            // Permission rows are uniform: key + description live in the title tooltip.
            ->assertSee('name="permissions[]" value="users.index"', false)
            ->assertSee('name="permissions[]" value="roles.index"', false)
            ->assertSee('title="users.index', false)
            ->assertSee('title="roles.index', false)
            // Botble-style limits with live counters and footer controls.
            ->assertSee('maxlength="120"', false)
            ->assertSee('maxlength="250"', false)
            ->assertSee('data-role-counter="120"', false)
            ->assertSee('data-role-counter="250"', false)
            ->assertSee('name="save_and_close"', false)
            ->assertSee('Save and close');
    }

    public function test_role_edit_form_renders_selected_permission_checked(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $role = Role::factory()->create();
        $permission = Permission::factory()->create([
            'key' => 'users.index',
            'name' => 'View users',
            'module' => 'core/base',
            'group' => 'users',
        ]);

        $role->permissions()->attach($permission);

        $this->actingAs($user, 'admin')
            ->get("/admin/system/roles/{$role->id}/edit")
            ->assertOk()
            ->assertSee('Permission Flags')
            // The assigned permission renders pre-checked inside the tree.
            ->assertSee('value="users.index" data-role-permission checked', false);
    }

    public function test_save_and_close_returns_to_roles_index_after_create(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->post('/admin/system/roles', [
                'name' => 'Reviewer',
                'save_and_close' => '1',
            ])
            ->assertRedirect('/admin/system/roles');

        $this->assertDatabaseHas('roles', [
            'slug' => 'reviewer',
        ]);
    }

    public function test_save_and_close_returns_to_roles_index_after_update(): void
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
            ->put("/admin/system/roles/{$role->id}", [
                'name' => 'Renamed role',
                'save_and_close' => '1',
            ])
            ->assertRedirect('/admin/system/roles');
    }

    public function test_role_name_cannot_exceed_120_characters(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->postJson('/admin/system/roles', [
                'name' => str_repeat('a', 121),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_role_description_cannot_exceed_250_characters(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->postJson('/admin/system/roles', [
                'name' => 'Valid name',
                'description' => str_repeat('a', 251),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
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
            ->delete("/admin/system/roles/{$role->id}")
            ->assertRedirect('/admin/system/roles')
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
            ->delete("/admin/system/roles/{$role->id}")
            ->assertRedirect('/admin/system/roles')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
        $this->assertDatabaseMissing('permission_role', [
            'role_id' => $role->id,
        ]);
    }
}
