<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Tests\TestCase;

class AdminUserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_requires_permission(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_super_admin_can_view_users_index(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        // /admin/users lists outside users only (not super admins, no built-in
        // Admin role), so the visible user is a plain account. The super admin
        // actor and Admin-role holders belong to /admin/system/users.
        $member = User::factory()->create([
            'name' => 'Member User',
            'email' => 'member@example.com',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Users')
            ->assertSee('Member User')
            ->assertSee('member@example.com');
    }

    public function test_outside_index_does_not_list_team_members(): void
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
            ->get('/admin/users')
            ->assertOk()
            ->assertDontSee('Team Member')
            ->assertDontSee('team-member@example.com');
    }

    public function test_super_admin_can_create_outside_user(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/users', [
                'name' => 'Content User',
                'username' => 'content-user',
                'email' => 'content@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_active' => '1',
            ])
            ->assertRedirect('/admin/users');

        $user = User::query()->where('email', 'content@example.com')->firstOrFail();

        $this->assertSame('Content User', $user->name);
        $this->assertSame('content-user', $user->username);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->is_super_admin);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertSame([], $user->roles()->pluck('roles.id')->all());
        $this->assertFalse($user->isTeamMember());
    }

    public function test_outside_store_ignores_roles_and_super_admin_payload(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $role = Role::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post('/admin/users', [
                'name' => 'Sneaky Payload',
                'email' => 'sneaky-payload@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_active' => '1',
                'is_super_admin' => '1',
                'roles' => [$role->id],
            ])
            ->assertRedirect('/admin/users')
            ->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'sneaky-payload@example.com')->firstOrFail();

        $this->assertFalse($user->is_super_admin);
        $this->assertSame([], $user->roles()->pluck('roles.id')->all());
    }

    public function test_created_outside_user_appears_outside_but_not_in_system_list(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/users', [
                'name' => 'Outside Only',
                'email' => 'outside-only@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_active' => '1',
            ])
            ->assertRedirect('/admin/users');

        $this->actingAs($admin, 'admin')
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Outside Only');

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users')
            ->assertOk()
            ->assertDontSee('Outside Only');
    }

    public function test_super_admin_can_update_outside_user_profile_status_and_password(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $role = Role::factory()->create();
        $user = User::factory()->create([
            'name' => 'Old User',
            'username' => 'old-user',
            'email' => 'old@example.com',
            'password' => Hash::make('old-password'),
            'is_active' => true,
            'is_super_admin' => false,
        ]);

        $this->actingAs($admin, 'admin')
            ->put("/admin/users/{$user->id}", [
                'name' => 'Updated User',
                'username' => 'updated-user',
                'email' => 'updated@example.com',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'is_super_admin' => '1',
                'roles' => [$role->id],
            ])
            ->assertRedirect("/admin/users/{$user->id}/edit")
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('Updated User', $user->name);
        $this->assertSame('updated-user', $user->username);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertFalse($user->is_active);
        // Privilege fields are ignored on the outside surface.
        $this->assertFalse($user->is_super_admin);
        $this->assertSame([], $user->roles()->pluck('roles.id')->all());
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    public function test_update_user_keeps_password_when_blank(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'is_active' => '1',
            ])
            ->assertRedirect("/admin/users/{$user->id}/edit");

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_user_email_must_be_unique(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/users', [
                'name' => 'Duplicate User',
                'email' => 'taken@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_team_member_cannot_be_managed_through_the_outside_surface(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $teamRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $teamMember = User::factory()->create(['is_active' => true]);
        $teamMember->roles()->attach($teamRole);

        $this->actingAs($admin, 'admin')
            ->get("/admin/users/{$teamMember->id}/edit")
            ->assertNotFound();

        $this->actingAs($admin, 'admin')
            ->put("/admin/users/{$teamMember->id}", [
                'name' => 'Hijacked',
                'email' => $teamMember->email,
            ])
            ->assertNotFound();

        $this->actingAs($admin, 'admin')
            ->delete("/admin/users/{$teamMember->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $teamMember->id,
        ]);
        $this->assertFalse($teamMember->fresh()->is_super_admin);
        $this->assertTrue($teamMember->fresh()->isTeamMember());
    }

    public function test_outside_user_cannot_delete_own_account(): void
    {
        $role = Role::factory()->create();
        $permission = Permission::query()->firstOrCreate(
            ['key' => 'users.delete'],
            ['name' => 'users.delete', 'module' => 'core/base', 'group' => 'users', 'description' => null],
        );
        $role->permissions()->attach($permission->id);

        $outsider = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);
        $outsider->roles()->attach($role);

        $this->actingAs($outsider, 'admin')
            ->delete("/admin/users/{$outsider->id}")
            ->assertRedirect('/admin/users')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $outsider->id,
        ]);
    }

    public function test_super_admin_can_delete_another_outside_user(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $role = Role::factory()->create();
        $user = User::factory()->create();

        $user->roles()->attach($role);

        $this->actingAs($admin, 'admin')
            ->delete("/admin/users/{$user->id}")
            ->assertRedirect('/admin/users')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
        $this->assertDatabaseMissing('role_user', [
            'user_id' => $user->id,
        ]);
    }

    public function test_users_index_search_matches_name_or_email(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        User::factory()->create([
            'name' => 'Content Creator',
            'email' => 'creator-content@example.com',
        ]);
        User::factory()->create([
            'name' => 'Other Person',
            'email' => 'other-person@example.com',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/users?q=Creator')
            ->assertOk()
            ->assertSee('Content Creator')
            ->assertDontSee('Other Person');

        $this->actingAs($admin, 'admin')
            ->get('/admin/users?q=other-person@example')
            ->assertOk()
            ->assertSee('Other Person')
            ->assertDontSee('Content Creator');
    }

    public function test_users_index_filters_by_status(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        User::factory()->create([
            'name' => 'Active Outsider',
            'email' => 'active-outsider@example.com',
            'is_active' => true,
        ]);
        User::factory()->create([
            'name' => 'Locked Outsider',
            'email' => 'locked-outsider@example.com',
            'is_active' => false,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/users?is_active=1')
            ->assertOk()
            ->assertSee('Active Outsider')
            ->assertDontSee('Locked Outsider');

        $this->actingAs($admin, 'admin')
            ->get('/admin/users?is_active=0')
            ->assertOk()
            ->assertSee('Locked Outsider')
            ->assertDontSee('Active Outsider');

        // Unknown values count as no filter at all.
        $this->actingAs($admin, 'admin')
            ->get('/admin/users?is_active=maybe')
            ->assertOk()
            ->assertSee('Active Outsider')
            ->assertSee('Locked Outsider');
    }

    public function test_users_index_filters_by_created_date_range(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $this->createUserCreatedOn('2026-06-15 09:00:00', [
            'name' => 'June user',
            'email' => 'june-user@example.com',
        ]);
        $this->createUserCreatedOn('2026-01-05 09:00:00', [
            'name' => 'January user',
            'email' => 'january-user@example.com',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/users?created_from=2026-06-01&created_to=2026-06-30')
            ->assertOk()
            ->assertSee('June user')
            ->assertDontSee('January user');

        // Only one bound set: the other side stays open.
        $this->actingAs($admin, 'admin')
            ->get('/admin/users?created_from=2026-02-01')
            ->assertOk()
            ->assertSee('June user')
            ->assertDontSee('January user');
    }

    public function test_users_index_ignores_invalid_created_date_filters(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $this->createUserCreatedOn('2026-06-15 09:00:00', [
            'name' => 'June user',
            'email' => 'june-user@example.com',
        ]);
        $this->createUserCreatedOn('2026-01-05 09:00:00', [
            'name' => 'January user',
            'email' => 'january-user@example.com',
        ]);

        // Non-dates and impossible dates count as no filter at all.
        $this->actingAs($admin, 'admin')
            ->get('/admin/users?created_from=not-a-date&created_to=2026-13-99')
            ->assertOk()
            ->assertSee('June user')
            ->assertSee('January user');
    }

    public function test_users_index_shows_active_filter_count_on_filters_button(): void
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
            ->get('/admin/users?is_active=0&created_from=2026-01-01')
            ->assertOk()
            ->assertSee('<span class="badge bg-blue-lt">2</span>', false);

        $this->actingAs($admin, 'admin')
            ->get('/admin/users')
            ->assertOk()
            ->assertDontSee('<span class="badge bg-blue-lt">', false);
    }

    private function createUserCreatedOn(string $createdAt, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->forceFill(['created_at' => $createdAt])->save();

        return $user->refresh();
    }
}
