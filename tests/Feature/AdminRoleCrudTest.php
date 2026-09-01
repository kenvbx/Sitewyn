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
            // Botble tree markup cloned 1:1 from core/acl::roles.permissions:
            // ul.list-feature#auto-checkboxes holding nested li.collapsed
            // nodes; the master "All Permissions" checkbox sits in the card
            // header (moved there per the project owner's instruction) with
            // Tabler header styling (form-check-inline m-0). The master is
            // wired functional in JS — a deviation from Botble, where both
            // the master and the .checker binding are inert legacy.
            // (data-name="foo" ships verbatim in Botble's markup.)
            ->assertSee('<label class="ms-auto form-check form-check-inline m-0">', false)
            ->assertSee('id="expandCollapseAllTree" class="label label-default allTree form-check-input"', false)
            ->assertSee('<span class="form-check-label">All Permissions</span>', false)
            ->assertSee('All Permissions')
            ->assertSee('<ul class="list-unstyled list-feature" id="auto-checkboxes" data-name="foo">', false)
            ->assertSee('li class="collapsed mx-0" style="background-color: inherit" id="node0"', false)
            ->assertSee('id="checkSelect0"', false)
            // Badge colors follow Botble's depth ladder: root nodes use
            // bg-*-lt primary badges, second level yellow, third level cyan.
            ->assertSee('<span class="badge bg-primary-lt">Users</span>', false)
            ->assertSee('<span class="badge bg-primary-lt">System</span>', false)
            ->assertSee('<span class="badge bg-yellow-lt">View users</span>', false)
            ->assertSee('<span class="badge bg-yellow-lt">System Users</span>', false)
            ->assertSee('<span class="badge bg-cyan-lt">View team users</span>', false)
            // Real permission checkboxes submit the original key via
            // permissions[]; grouping nodes (Users, System Users) submit
            // nothing because their dot paths are not permissions.
            ->assertSee('name="permissions[]" class="form-check-input" value="users.index"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="roles.index"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="page.index"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="media.upload"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="system.users.create"', false)
            ->assertSee('id="checkSelect_sub_11_0" class="form-check-input">', false)
            // Botble loads jquery-ui + jqueryTree + role.js for this screen;
            // the Sitewyn push loads the same libraries and init code.
            ->assertSee('vendor/core-base/libraries/jquery.min.js', false)
            ->assertSee('vendor/core-base/libraries/jquery-ui/jquery-ui.min.js', false)
            ->assertSee('vendor/core-base/libraries/jquery-tree/jquery.tree.min.js', false)
            ->assertSee('\'#auto-checkboxes li\').tree(', false)
            // The header master is wired up (deviation from inert Botble —
            // JS behavior itself is browser-only, untestable here); the
            // dead #mainNode .checker binding is dropped.
            ->assertSee('\'#expandCollapseAllTree\')', false)
            ->assertDontSee('\'#mainNode .checker\'', false)
            // The previous in-house tree UI is gone completely.
            ->assertDontSee('data-module-card', false)
            ->assertDontSee('data-module-body', false)
            ->assertDontSee('data-module-master', false)
            ->assertDontSee('data-module-collapse', false)
            ->assertDontSee('data-group-block', false)
            ->assertDontSee('data-group-master', false)
            ->assertDontSee('data-perm-item', false)
            ->assertDontSee('data-role-permission', false)
            ->assertDontSee('data-role-all-master', false)
            ->assertDontSee('data-role-all-permissions', false)
            ->assertDontSee('data-role-collapse-all', false)
            ->assertDontSee('data-role-expand-all', false)
            ->assertDontSee('bg-green-lt', false)
            ->assertDontSee('bg-orange-lt', false)
            ->assertDontSee('Collapse all')
            ->assertDontSee('Expand all')
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
            // The assigned permission renders pre-checked inside the Botble
            // tree (name, class, value, checked — Botble attribute order).
            ->assertSee('name="permissions[]" class="form-check-input" value="users.index" checked', false);
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
