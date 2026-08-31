<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        // Only team members are listed on /admin/users (super admins and
        // Admin-role holders), so the other visible user needs the Admin role.
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $editor = User::factory()->create([
            'name' => 'Editor User',
            'email' => 'editor@example.com',
        ]);
        $editor->roles()->attach($adminRole);

        $this->actingAs($admin, 'admin')
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Users')
            ->assertSee('Editor User')
            ->assertSee('editor@example.com');
    }

    public function test_super_admin_can_create_user_with_roles(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $role = Role::factory()->create([
            'name' => 'Editor',
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/users', [
                'name' => 'Content User',
                'username' => 'content-user',
                'email' => 'content@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_active' => '1',
                'roles' => [$role->id],
            ])
            ->assertRedirect('/admin/users');

        $user = User::query()->where('email', 'content@example.com')->firstOrFail();

        $this->assertSame('Content User', $user->name);
        $this->assertSame('content-user', $user->username);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->is_super_admin);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertSame([$role->id], $user->roles()->pluck('roles.id')->all());
    }

    public function test_super_admin_can_update_user_profile_roles_status_and_password(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $oldRole = Role::factory()->create();
        $newRole = Role::factory()->create();
        $user = User::factory()->create([
            'name' => 'Old User',
            'username' => 'old-user',
            'email' => 'old@example.com',
            'password' => Hash::make('old-password'),
            'is_active' => true,
            'is_super_admin' => false,
        ]);

        $user->roles()->attach($oldRole);

        $this->actingAs($admin, 'admin')
            ->put("/admin/users/{$user->id}", [
                'name' => 'Updated User',
                'username' => 'updated-user',
                'email' => 'updated@example.com',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'is_super_admin' => '1',
                'roles' => [$newRole->id],
            ])
            ->assertRedirect("/admin/users/{$user->id}/edit");

        $user->refresh();

        $this->assertSame('Updated User', $user->name);
        $this->assertSame('updated-user', $user->username);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertFalse($user->is_active);
        $this->assertTrue($user->is_super_admin);
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertSame([$newRole->id], $user->roles()->pluck('roles.id')->all());
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

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->delete("/admin/users/{$admin->id}")
            ->assertRedirect('/admin/users')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_super_admin_can_delete_another_user(): void
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
}
