<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Tests\TestCase;

class AdminPermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth:admin', 'permission:users.index'])
            ->get('/admin/_test-permission/users', fn () => response('allowed'));

        Route::middleware(['web', 'auth:admin', 'permission'])
            ->get('/admin/_test-permission/empty', fn () => response('allowed'));
    }

    public function test_admin_with_required_permission_can_access_route(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $role = Role::factory()->create();
        $permission = Permission::factory()->create([
            'key' => 'users.index',
        ]);

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->actingAs($user, 'admin')
            ->get('/admin/_test-permission/users')
            ->assertOk()
            ->assertSee('allowed');
    }

    public function test_admin_without_required_permission_gets_forbidden(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->get('/admin/_test-permission/users')
            ->assertForbidden();
    }

    public function test_super_admin_can_access_permission_protected_route(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->get('/admin/_test-permission/users')
            ->assertOk()
            ->assertSee('allowed');
    }

    public function test_permission_middleware_without_permission_argument_is_forbidden(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->get('/admin/_test-permission/empty')
            ->assertForbidden();
    }
}
