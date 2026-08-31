<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Tests\TestCase;

class AdminSystemUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_users_index_requires_the_team_permission(): void
    {
        // users.index alone is the outside surface permission — the team
        // surface needs system.users.index.
        $user = $this->userWithPermissions(['users.index']);

        $this->actingAs($user, 'admin')
            ->get('/admin/system/users')
            ->assertForbidden();
    }

    public function test_super_admin_can_view_team_users_index(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $teamRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $teamMember = User::factory()->create([
            'name' => 'Team Member',
            'email' => 'team-member@example.com',
        ]);
        $teamMember->roles()->attach($teamRole);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users')
            ->assertOk()
            ->assertSee('Team users')
            ->assertSee('Team Member')
            ->assertSee('team-member@example.com');
    }

    public function test_system_users_index_does_not_list_outside_users(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        User::factory()->create([
            'name' => 'Plain Member',
            'email' => 'plain-member@example.com',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users')
            ->assertOk()
            ->assertDontSee('Plain Member')
            ->assertDontSee('plain-member@example.com');
    }

    public function test_super_admin_can_create_team_user_with_the_admin_role(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/system/users', [
                'name' => 'New Teammate',
                'username' => 'new-teammate',
                'email' => 'new-teammate@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_active' => '1',
                'roles' => [$adminRole->id],
            ])
            ->assertRedirect('/admin/system/users')
            ->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'new-teammate@example.com')->firstOrFail();

        $this->assertTrue($user->is_active);
        $this->assertFalse($user->is_super_admin);
        $this->assertTrue($user->isTeamMember());
        $this->assertSame([$adminRole->id], $user->roles()->pluck('roles.id')->all());

        // The new team member shows up on the team list only.
        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users')
            ->assertOk()
            ->assertSee('New Teammate');

        $this->actingAs($admin, 'admin')
            ->get('/admin/users')
            ->assertOk()
            ->assertDontSee('New Teammate');
    }

    public function test_super_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->delete("/admin/system/users/{$admin->id}")
            ->assertRedirect('/admin/system/users')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_super_admin_can_delete_another_team_user(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $teamMember = User::factory()->create();
        $teamMember->roles()->attach($adminRole);

        $this->actingAs($admin, 'admin')
            ->delete("/admin/system/users/{$teamMember->id}")
            ->assertRedirect('/admin/system/users')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('users', [
            'id' => $teamMember->id,
        ]);
        $this->assertDatabaseMissing('role_user', [
            'user_id' => $teamMember->id,
        ]);
    }

    public function test_team_users_index_filters_by_status(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $activeMember = User::factory()->create([
            'name' => 'Active Teammate',
            'email' => 'active-teammate@example.com',
            'is_active' => true,
        ]);
        $activeMember->roles()->attach($adminRole);
        $lockedMember = User::factory()->create([
            'name' => 'Locked Teammate',
            'email' => 'locked-teammate@example.com',
            'is_active' => false,
        ]);
        $lockedMember->roles()->attach($adminRole);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?is_active=1')
            ->assertOk()
            ->assertSee('Active Teammate')
            ->assertDontSee('Locked Teammate');

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?is_active=0')
            ->assertOk()
            ->assertSee('Locked Teammate')
            ->assertDontSee('Active Teammate');

        // Unknown values count as no filter at all.
        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?is_active=maybe')
            ->assertOk()
            ->assertSee('Active Teammate')
            ->assertSee('Locked Teammate');
    }

    public function test_team_users_index_filters_by_role(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $editorRole = Role::factory()->create([
            'name' => 'Editor',
            'slug' => 'editor',
        ]);
        $editorTeammate = User::factory()->create([
            'name' => 'Editor Teammate',
            'email' => 'editor-teammate@example.com',
        ]);
        $editorTeammate->roles()->attach([$adminRole->id, $editorRole->id]);
        $plainTeammate = User::factory()->create([
            'name' => 'Plain Teammate',
            'email' => 'plain-teammate@example.com',
        ]);
        $plainTeammate->roles()->attach($adminRole);

        // Only teammates holding the Editor role match its id.
        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?role='.$editorRole->id)
            ->assertOk()
            ->assertSee('Editor Teammate')
            ->assertDontSee('Plain Teammate');

        // The sentinel value filters super admins only.
        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?role=super')
            ->assertOk()
            ->assertSee($admin->email)
            ->assertDontSee('Editor Teammate')
            ->assertDontSee('Plain Teammate');
    }

    public function test_team_users_index_filters_by_created_date_range(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $this->createTeamMemberCreatedOn('2026-06-15 09:00:00', $adminRole, [
            'name' => 'June teammate',
            'email' => 'june-teammate@example.com',
        ]);
        $this->createTeamMemberCreatedOn('2026-01-05 09:00:00', $adminRole, [
            'name' => 'January teammate',
            'email' => 'january-teammate@example.com',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?created_from=2026-06-01&created_to=2026-06-30')
            ->assertOk()
            ->assertSee('June teammate')
            ->assertDontSee('January teammate');

        // Only one bound set: the other side stays open.
        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?created_from=2026-02-01')
            ->assertOk()
            ->assertSee('June teammate')
            ->assertDontSee('January teammate');
    }

    public function test_team_users_index_ignores_invalid_created_date_filters(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $this->createTeamMemberCreatedOn('2026-06-15 09:00:00', $adminRole, [
            'name' => 'June teammate',
            'email' => 'june-teammate@example.com',
        ]);
        $this->createTeamMemberCreatedOn('2026-01-05 09:00:00', $adminRole, [
            'name' => 'January teammate',
            'email' => 'january-teammate@example.com',
        ]);

        // Non-dates and impossible dates count as no filter at all.
        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?created_from=not-a-date&created_to=2026-13-99')
            ->assertOk()
            ->assertSee('June teammate')
            ->assertSee('January teammate');
    }

    public function test_team_users_index_shows_active_filter_count_on_filters_button(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $member = User::factory()->create([
            'name' => 'Role Teammate',
            'email' => 'role-teammate@example.com',
        ]);
        $member->roles()->attach($adminRole);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?is_active=0&created_from=2026-01-01')
            ->assertOk()
            ->assertSee('<span class="badge bg-blue-lt">2</span>', false);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users')
            ->assertOk()
            ->assertDontSee('<span class="badge bg-blue-lt">', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTeamMemberCreatedOn(string $createdAt, Role $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach($role);
        $user->forceFill(['created_at' => $createdAt])->save();

        return $user->refresh();
    }

    /**
     * @param  array<int, string>  $permissionKeys
     */
    private function userWithPermissions(array $permissionKeys): User
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);
        $role = Role::factory()->create();

        foreach ($permissionKeys as $key) {
            $role->permissions()->attach(
                Permission::query()->firstOrCreate(
                    ['key' => $key],
                    ['name' => $key, 'module' => 'core/base', 'group' => 'system users', 'description' => null],
                ),
            );
        }

        $user->roles()->attach($role);

        return $user;
    }
}
