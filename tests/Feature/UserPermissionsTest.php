<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_check_permissions_from_roles(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
        ]);

        $role = Role::factory()->create();
        $editPosts = Permission::factory()->create([
            'key' => 'posts.edit',
        ]);
        $viewPosts = Permission::factory()->create([
            'key' => 'posts.index',
        ]);
        Permission::factory()->create([
            'key' => 'settings.edit',
        ]);

        $role->permissions()->attach([$editPosts->id, $viewPosts->id]);
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('posts.edit'));
        $this->assertTrue($user->hasPermission($viewPosts));
        $this->assertTrue($user->hasAnyPermission(['settings.edit', 'posts.edit']));
        $this->assertTrue($user->hasAllPermissions(['posts.index', 'posts.edit']));
        $this->assertFalse($user->hasPermission('settings.edit'));
        $this->assertFalse($user->hasAnyPermission(['settings.edit', 'users.delete']));
        $this->assertFalse($user->hasAllPermissions(['posts.index', 'settings.edit']));
        $this->assertSame(['posts.edit', 'posts.index'], $user->permissionKeys()->all());
    }

    public function test_super_admin_bypasses_permission_checks(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
        ]);

        Permission::factory()->create([
            'key' => 'users.index',
        ]);

        $this->assertTrue($user->hasPermission('missing.permission'));
        $this->assertTrue($user->hasAnyPermission([]));
        $this->assertTrue($user->hasAllPermissions([]));
        $this->assertSame(['users.index'], $user->permissionKeys()->all());
    }

    public function test_permission_checks_are_available_through_gate_and_blade_can(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
        ]);

        $role = Role::factory()->create();
        $permission = Permission::factory()->create([
            'key' => 'users.index',
        ]);

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->assertTrue(Gate::forUser($user)->allows('users.index'));
        $this->assertFalse(Gate::forUser($user)->allows('users.delete'));

        $this->actingAs($user, 'admin');

        $html = Blade::render(
            <<<'BLADE'
            @can('users.index')
                allowed
            @endcan
            @can('users.delete')
                denied
            @endcan
            BLADE,
            [],
            deleteCachedView: true,
        );

        $this->assertStringContainsString('allowed', $html);
        $this->assertStringNotContainsString('denied', $html);
    }
}
