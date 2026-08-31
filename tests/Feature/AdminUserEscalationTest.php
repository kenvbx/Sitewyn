<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Tests\TestCase;

class AdminUserEscalationTest extends TestCase
{
    use RefreshDatabase;

    // Escalation guards live on the team surface (/admin/system/users,
    // SystemUserController) — the only surface that accepts roles and the
    // super admin flag. The outside surface (/admin/users) cannot escalate
    // because it ignores both fields entirely.
    private const SURFACE = '/admin/system/users';

    public function test_admin_with_edit_permission_cannot_change_own_privileges(): void
    {
        $editor = $this->userWithPermissions(['system.users.edit']);
        $ownRole = $editor->roles()->first();
        $otherRole = Role::factory()->create();

        $this->actingAs($editor, 'admin')
            ->put(self::SURFACE."/{$editor->id}", [
                'name' => 'Renamed Editor',
                'username' => 'renamed-editor',
                'email' => $editor->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'is_active' => '',
                'is_super_admin' => '1',
                'roles' => [$otherRole->id],
            ])
            ->assertRedirect(self::SURFACE."/{$editor->id}/edit")
            ->assertSessionHasNoErrors();

        $editor->refresh();

        $this->assertSame('Renamed Editor', $editor->name);
        $this->assertSame('renamed-editor', $editor->username);
        $this->assertTrue(Hash::check('new-password', $editor->password));
        $this->assertFalse($editor->is_super_admin);
        $this->assertTrue($editor->is_active);
        $this->assertSame([$ownRole->id], $editor->roles()->pluck('roles.id')->all());
    }

    public function test_super_admin_editing_own_profile_keeps_the_super_admin_flag(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $role = Role::factory()->create();
        $admin->roles()->attach($role);

        $this->actingAs($admin, 'admin')
            ->put(self::SURFACE."/{$admin->id}", [
                'name' => 'Still Super',
                'username' => $admin->username,
                'email' => $admin->email,
            ])
            ->assertRedirect(self::SURFACE."/{$admin->id}/edit")
            ->assertSessionHasNoErrors();

        $admin->refresh();

        $this->assertSame('Still Super', $admin->name);
        $this->assertTrue($admin->is_super_admin);
    }

    public function test_non_super_admin_cannot_grant_the_super_admin_flag_on_update(): void
    {
        $editor = $this->userWithPermissions(['system.users.edit']);
        $target = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($editor, 'admin')
            ->put(self::SURFACE."/{$target->id}", [
                'name' => $target->name,
                'username' => $target->username,
                'email' => $target->email,
                'is_super_admin' => '1',
            ])
            ->assertSessionHasErrors('is_super_admin');

        $this->assertStringContainsString(
            'Only super admins can grant the super admin flag.',
            (string) session('errors')->first('is_super_admin'),
        );
        $this->assertFalse($target->fresh()->is_super_admin);
    }

    public function test_non_super_admin_cannot_grant_the_super_admin_flag_on_store(): void
    {
        $editor = $this->userWithPermissions(['system.users.create']);

        $this->actingAs($editor, 'admin')
            ->post(self::SURFACE, [
                'name' => 'Escalated User',
                'email' => 'escalated@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_super_admin' => '1',
            ])
            ->assertSessionHasErrors('is_super_admin');

        $this->assertStringContainsString(
            'Only super admins can grant the super admin flag.',
            (string) session('errors')->first('is_super_admin'),
        );
        $this->assertDatabaseMissing('users', ['email' => 'escalated@example.com']);
    }

    public function test_super_admin_can_grant_the_super_admin_flag_on_update(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);
        $role = Role::factory()->create();

        $this->actingAs($admin, 'admin')
            ->put(self::SURFACE."/{$target->id}", [
                'name' => $target->name,
                'username' => $target->username,
                'email' => $target->email,
                'is_super_admin' => '1',
                'roles' => [$role->id],
            ])
            ->assertRedirect(self::SURFACE."/{$target->id}/edit")
            ->assertSessionHasNoErrors();

        $target->refresh();

        $this->assertTrue($target->is_super_admin);
        $this->assertSame([$role->id], $target->roles()->pluck('roles.id')->all());
    }

    public function test_super_admin_can_store_user_with_the_super_admin_flag(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(self::SURFACE, [
                'name' => 'New Super',
                'email' => 'new-super@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_super_admin' => '1',
            ])
            ->assertRedirect(self::SURFACE)
            ->assertSessionHasNoErrors();

        $this->assertTrue(
            User::query()->where('email', 'new-super@example.com')->firstOrFail()->is_super_admin,
        );
    }

    public function test_non_super_admin_cannot_assign_a_role_with_permissions_they_do_not_have(): void
    {
        $editor = $this->userWithPermissions(['system.users.edit']);
        $target = User::factory()->create(['is_active' => true]);
        $currentRole = Role::factory()->create();
        $target->roles()->attach($currentRole);

        $forbiddenRole = Role::factory()->create();
        $this->attachPermissions($forbiddenRole, ['users.edit', 'users.delete']);

        $this->actingAs($editor, 'admin')
            ->put(self::SURFACE."/{$target->id}", [
                'name' => $target->name,
                'username' => $target->username,
                'email' => $target->email,
                'roles' => [$forbiddenRole->id],
            ])
            ->assertSessionHasErrors('roles');

        $this->assertStringContainsString(
            'You cannot assign a role with permissions you do not have.',
            (string) session('errors')->first('roles'),
        );
        $this->assertSame([$currentRole->id], $target->roles()->pluck('roles.id')->all());
    }

    public function test_non_super_admin_can_assign_roles_within_their_own_permissions(): void
    {
        $editor = $this->userWithPermissions(['system.users.edit', 'system.users.create']);
        $target = User::factory()->create(['is_active' => true]);

        $allowedRole = Role::factory()->create();
        $this->attachPermissions($allowedRole, ['system.users.edit']);
        $emptyRole = Role::factory()->create();

        $this->actingAs($editor, 'admin')
            ->put(self::SURFACE."/{$target->id}", [
                'name' => $target->name,
                'username' => $target->username,
                'email' => $target->email,
                'roles' => [$allowedRole->id, $emptyRole->id],
            ])
            ->assertRedirect(self::SURFACE."/{$target->id}/edit")
            ->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(
            [$allowedRole->id, $emptyRole->id],
            $target->roles()->pluck('roles.id')->all(),
        );
    }

    public function test_non_super_admin_cannot_assign_a_role_with_extra_permissions_on_store(): void
    {
        $editor = $this->userWithPermissions(['system.users.create']);
        $forbiddenRole = Role::factory()->create();
        $this->attachPermissions($forbiddenRole, ['users.delete']);

        $this->actingAs($editor, 'admin')
            ->post(self::SURFACE, [
                'name' => 'Sneaky User',
                'email' => 'sneaky@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'roles' => [$forbiddenRole->id],
            ])
            ->assertSessionHasErrors('roles');

        $this->assertStringContainsString(
            'You cannot assign a role with permissions you do not have.',
            (string) session('errors')->first('roles'),
        );
        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_user_with_edit_permission_cannot_store_users_without_create_permission(): void
    {
        $editor = $this->userWithPermissions(['system.users.edit']);

        $this->actingAs($editor, 'admin')
            ->post(self::SURFACE, [
                'name' => 'Gate User',
                'email' => 'gate@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'gate@example.com']);
    }

    public function test_edit_form_hides_the_super_admin_toggle_and_filters_roles_for_non_super_admins(): void
    {
        $editor = $this->userWithPermissions(['system.users.edit']);
        $allowedRole = $editor->roles()->first();
        $forbiddenRole = Role::factory()->create(['name' => 'Forbidden Role']);
        $this->attachPermissions($forbiddenRole, ['users.delete']);
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($editor, 'admin')
            ->get(self::SURFACE."/{$target->id}/edit")
            ->assertOk()
            ->assertDontSee('Super Admin')
            ->assertSee($allowedRole->name)
            ->assertDontSee($forbiddenRole->name);
    }

    public function test_edit_form_shows_the_super_admin_toggle_and_all_roles_to_super_admins(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $roleA = Role::factory()->create(['name' => 'Allowed Role']);
        $roleB = Role::factory()->create(['name' => 'Forbidden Role']);
        $this->attachPermissions($roleB, ['users.delete']);
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->get(self::SURFACE."/{$target->id}/edit")
            ->assertOk()
            ->assertSee('Super Admin')
            ->assertSee('Allowed Role')
            ->assertSee('Forbidden Role');
    }

    public function test_create_form_hides_the_super_admin_toggle_and_filters_roles_for_non_super_admins(): void
    {
        $editor = $this->userWithPermissions(['system.users.create']);
        $allowedRole = $editor->roles()->first();
        $forbiddenRole = Role::factory()->create(['name' => 'Forbidden Role']);
        $this->attachPermissions($forbiddenRole, ['users.delete']);

        $this->actingAs($editor, 'admin')
            ->get(self::SURFACE.'/create')
            ->assertOk()
            ->assertDontSee('Super Admin')
            ->assertSee($allowedRole->name)
            ->assertDontSee($forbiddenRole->name);
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
        $this->attachPermissions($role, $permissionKeys);
        $user->roles()->attach($role);

        return $user;
    }

    /**
     * @param  array<int, string>  $permissionKeys
     */
    private function attachPermissions(Role $role, array $permissionKeys): void
    {
        $permissions = collect($permissionKeys)
            ->map(fn (string $key): Permission => Permission::query()->firstOrCreate(
                ['key' => $key],
                [
                    'name' => $key,
                    'module' => 'core/base',
                    'group' => str($key)->before('.')->toString(),
                    'description' => null,
                ],
            ));

        $role->permissions()->attach($permissions->pluck('id')->all());
    }
}
