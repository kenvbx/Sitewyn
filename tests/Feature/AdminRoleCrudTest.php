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
            // Botble tree markup cloned 1:1 from the rendered ACL roles
            // screen of the current Botble: div.permissions-tree with
            // ul.parent_tree module cards (li.permissions-item +
            // div.permissions-header + ul.row.permissions-body) and
            // nested feature/sub/leaf lists. The hitarea divs,
            // expandable/collapsable classes and display:none are added
            // at runtime by the classic jquery-treeview plugin Botble
            // binds in its acl role.js, so the blade ships the plain
            // lists the plugin expects. The master "All Permissions"
            // checkbox sits in the card header (moved there per the
            // project owner's instruction) and is wired functional in JS
            // — a deviation from Botble, where the tree master is a flat
            // check-all rendered inside the card body.
            // (data-name="foo" ships verbatim in Botble's markup.)
            ->assertSee('<label class="ms-3 form-check">', false)
            ->assertSee('id="expandCollapseAllTree" class="form-check-input label label-default allTree"', false)
            ->assertSee('<span class="form-check-label">All Permissions</span>', false)
            // The ms-auto slot moved from the master label to the
            // collapse/expand links on the right of the header (master
            // stays left next to the title); the links are wired by
            // wireCollapseAll() — JS behavior itself is browser-only,
            // untestable here.
            ->assertSee('<a href="#" id="collapseAllTree" class="link-secondary text-decoration-none">Collapse all</a>', false)
            ->assertSee('<span class="text-secondary">|</span>', false)
            ->assertSee('<a href="#" id="expandAllTree" class="link-secondary text-decoration-none">Expand all</a>', false)
            ->assertSee('<div class="permissions-tree" id="checkboxes-permisstions" data-name="foo">', false)
            ->assertSee('<ul class="parent_tree m-0 p-0 list-unstyled" id="node0">', false)
            ->assertSee('<li class="permissions-item list-unstyled">', false)
            ->assertSee('<div class="permissions-header">', false)
            ->assertSee('<ul class="row permissions-body has-children">', false)
            // Module cards follow the registry modules: Core / Pages /
            // Blog / Media, success badges, checkbox without name (the
            // module is a grouping node, not a permission).
            ->assertSee('<span class="badge bg-success-lt">Core</span>', false)
            ->assertSee('<span class="badge bg-success-lt">Pages</span>', false)
            ->assertSee('<span class="badge bg-success-lt">Blog</span>', false)
            ->assertSee('<span class="badge bg-success-lt">Media</span>', false)
            ->assertSee('<input type="checkbox" id="checkbox_one_0" class="form-check-input check-success">', false)
            // Feature groups render as col-4 items with primary badges;
            // Botble's node ids (node_sub_m_f / node_sub_sub_n /
            // node_grand_childn) and checkbox ids (checkbox_two/three/
            // four) are preserved. System Users is the only sub level
            // (yellow badge, grouping checkbox without name).
            ->assertSee('<li class="list-unstyled col-4 m-0" style="background-color: inherit" id="node_sub_0_0">', false)
            ->assertSee('<li style="background-color: inherit" id="node_sub_sub_0">', false)
            ->assertSee('<li style="background-color: inherit" id="node_grand_child0">', false)
            ->assertSee('<input type="checkbox" id="checkbox_two_0_0" name="permissions[]" class="form-check-input" value="users.index"', false)
            ->assertSee('<input type="checkbox" id="checkbox_two_0_1" class="form-check-input">', false)
            ->assertSee('<input type="checkbox" id="checkbox_three_0" class="form-check-input check-yellow">', false)
            ->assertSee('<span class="badge bg-primary-lt">Users</span>', false)
            ->assertSee('<span class="badge bg-primary-lt">System</span>', false)
            ->assertSee('<span class="badge bg-primary-lt">Roles</span>', false)
            ->assertSee('<span class="badge bg-primary-lt">Posts</span>', false)
            ->assertSee('<span class="badge bg-yellow-lt">System Users</span>', false)
            // Real permission checkboxes submit the original key via
            // permissions[]; the group's .index permission rides on the
            // feature checkbox, remaining actions and single-action
            // features (Settings/Plugins/Backups/Menus/Widgets) render as
            // leaves with the short verbs Botble's leaves use.
            ->assertSee('name="permissions[]" class="form-check-input" value="page.index"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="post.index"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="category.index"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="tag.index"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="media.index"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="roles.index"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="permissions.index"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="audit.index"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="media.upload"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="system.users.index"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="system.users.create"', false)
            ->assertSee('name="permissions[]" class="form-check-input" value="menus.manage"', false)
            ->assertSee('<span class="form-check-label">Create</span>', false)
            ->assertSee('<span class="form-check-label">Edit</span>', false)
            ->assertSee('<span class="form-check-label">Delete</span>', false)
            ->assertSee('<span class="form-check-label">View list</span>', false)
            ->assertSee('<span class="form-check-label">Manage</span>', false)
            ->assertSee('<span class="form-check-label">Upload</span>', false)
            // Botble core.css .permissions-tree rules verbatim (the
            // .daredevel-tree rules of the old markup are gone with it),
            // plus the --bb-bg-* values the dark-mode rules consume
            // (copied from Botble's core.css :root / dark blocks).
            ->assertSee('[data-bs-theme=dark] .permissions-tree .permissions-item{background-color:var(--bb-bg-forms)}', false)
            ->assertSee('.permissions-tree .permissions-item{background-color:#f6f8fb;border-radius:4px;margin-bottom:10px;padding:0}', false)
            ->assertSee('.permissions-tree .permissions-item .permissions-header{background-color:#f2f5f7;border-bottom:1px solid #cfd7e0}', false)
            ->assertSee('.permissions-tree .form-check .form-check-input.check-success:checked{background-color:#198754}', false)
            ->assertSee('.permissions-tree .form-check .form-check-input.check-yellow:checked{background-color:#efc656}', false)
            ->assertSee('--bb-bg-forms: #fff', false)
            ->assertSee('--bb-bg-forms: #111827', false)
            // Botble loads jQuery + the classic jquery-treeview plugin for
            // this tree and binds its acl role.js init + checkbox
            // cascade; the Sitewyn push loads the same libraries and
            // init code.
            ->assertSee('vendor/core-base/libraries/jquery.min.js', false)
            ->assertSee('vendor/core-base/libraries/jquery-treeview/jquery.treeview.min.css', false)
            ->assertSee('vendor/core-base/libraries/jquery-treeview/jquery.treeview.min.js', false)
            ->assertSee('$(value).treeview({', false)
            ->assertSee("$('#checkboxes-permisstions :checkbox').on('click', function (event) {", false)
            // The header master is wired up (deviation from flat Botble
            // master — JS behavior itself is browser-only, untestable
            // here).
            ->assertSee('\'#expandCollapseAllTree\')', false)
            // The old daredevel jquery-tree markup and its libraries are
            // gone completely, along with the previous in-house tree UI.
            ->assertDontSee('mainNode', false)
            ->assertDontSee('auto-checkboxes', false)
            ->assertDontSee('daredevel', false)
            ->assertDontSee('jquery-ui', false)
            ->assertDontSee('list-feature', false)
            ->assertDontSee('collapsed mx-0', false)
            ->assertDontSee('checkSelect', false)
            ->assertDontSee('flags[]', false)
            ->assertDontSee('bg-cyan-lt', false)
            ->assertDontSee('bg-lime-lt', false)
            ->assertDontSee('bg-purple-lt', false)
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
