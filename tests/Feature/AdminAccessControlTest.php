<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Tests\TestCase;

class AdminAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_protected_admin_routes(): void
    {
        foreach ($this->protectedAdminRoutes() as $path) {
            $this->get($path)
                ->assertRedirect('/admin/login');
        }
    }

    public function test_admin_without_required_permission_is_forbidden(): void
    {
        foreach ($this->permissionRoutes() as $route) {
            $user = User::factory()->create([
                'is_super_admin' => false,
                'is_active' => true,
            ]);

            $this->actingAs($user, 'admin')
                ->get($route['path'])
                ->assertForbidden();
        }
    }

    public function test_admin_with_required_permission_can_access_route(): void
    {
        foreach ($this->permissionRoutes() as $route) {
            $user = User::factory()->create([
                'is_super_admin' => false,
                'is_active' => true,
            ]);
            $role = Role::factory()->create();
            $permission = Permission::query()->firstOrCreate(
                ['key' => $route['permission']],
                [
                    'name' => $route['permission'],
                    'module' => 'core/base',
                    'group' => str($route['permission'])->before('.')->toString(),
                    'description' => null,
                ],
            );

            $role->permissions()->attach($permission);
            $user->roles()->attach($role);

            $this->actingAs($user, 'admin')
                ->get($route['path'])
                ->assertOk();
        }
    }

    public function test_super_admin_can_access_protected_admin_routes(): void
    {
        foreach ($this->protectedAdminRoutes() as $path) {
            $user = User::factory()->create([
                'is_super_admin' => true,
                'is_active' => true,
            ]);

            $this->actingAs($user, 'admin')
                ->get($path)
                ->assertOk();
        }
    }

    public function test_login_rejects_bad_password_and_keeps_user_guest(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'is_active' => true,
        ]);

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'bad-password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    /**
     * @return array<int, string>
     */
    private function protectedAdminRoutes(): array
    {
        return [
            '/admin',
            '/admin/users',
            '/admin/roles',
            '/admin/permissions',
            '/admin/settings',
            '/admin/media',
        ];
    }

    /**
     * @return array<int, array{path: string, permission: string}>
     */
    private function permissionRoutes(): array
    {
        return [
            ['path' => '/admin/users', 'permission' => 'users.index'],
            ['path' => '/admin/roles', 'permission' => 'roles.index'],
            ['path' => '/admin/permissions', 'permission' => 'permissions.index'],
            ['path' => '/admin/settings', 'permission' => 'settings.edit'],
            ['path' => '/admin/media', 'permission' => 'media.index'],
        ];
    }
}
