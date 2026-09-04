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
            '/admin/system/users',
            '/admin/system/roles',
            '/admin/system/backups',
            '/admin/system/cronjob',
            '/admin/system/security',
            '/admin/system/cache',
            '/admin/system/cleanup',
            '/admin/system/info',
            '/admin/system/updater',
            '/admin/permissions',
            '/admin/settings',
            '/admin/settings/general',
            '/admin/settings/email',
            '/admin/settings/email/templates',
            '/admin/settings/email/rules',
            '/admin/settings/phone-number',
            '/admin/settings/media',
            '/admin/settings/permalink',
            '/admin/settings/admin-appearance',
            '/admin/settings/api',
            '/admin/settings/cache',
            '/admin/settings/datatables',
            '/admin/settings/website-tracking',
            '/admin/settings/optimize',
            '/admin/settings/blog',
            '/admin/settings/members',
            '/admin/translations/locales',
            '/admin/media',
            '/admin/request-logs',
        ];
    }

    /**
     * @return array<int, array{path: string, permission: string}>
     */
    private function permissionRoutes(): array
    {
        return [
            ['path' => '/admin/users', 'permission' => 'users.index'],
            ['path' => '/admin/system/users', 'permission' => 'system.users.index'],
            ['path' => '/admin/system/roles', 'permission' => 'roles.index'],
            ['path' => '/admin/system/backups', 'permission' => 'backups.manage'],
            ['path' => '/admin/system/cronjob', 'permission' => 'cronjobs.manage'],
            ['path' => '/admin/system/security', 'permission' => 'security.manage'],
            ['path' => '/admin/system/cache', 'permission' => 'settings.cache'],
            ['path' => '/admin/system/cleanup', 'permission' => 'cleanup.manage'],
            ['path' => '/admin/system/info', 'permission' => 'system.info'],
            ['path' => '/admin/system/updater', 'permission' => 'system.updater'],
            ['path' => '/admin/permissions', 'permission' => 'permissions.index'],
            ['path' => '/admin/settings', 'permission' => 'settings.edit'],
            ['path' => '/admin/settings/general', 'permission' => 'settings.edit'],
            ['path' => '/admin/settings/email', 'permission' => 'settings.email'],
            ['path' => '/admin/settings/email/templates', 'permission' => 'settings.email'],
            ['path' => '/admin/settings/email/rules', 'permission' => 'settings.email_rules'],
            ['path' => '/admin/settings/phone-number', 'permission' => 'settings.phone_number'],
            ['path' => '/admin/settings/media', 'permission' => 'settings.media'],
            ['path' => '/admin/settings/permalink', 'permission' => 'settings.permalink'],
            ['path' => '/admin/settings/admin-appearance', 'permission' => 'settings.admin_appearance'],
            ['path' => '/admin/settings/api', 'permission' => 'settings.api'],
            ['path' => '/admin/settings/cache', 'permission' => 'settings.cache'],
            ['path' => '/admin/settings/datatables', 'permission' => 'settings.datatables'],
            ['path' => '/admin/settings/website-tracking', 'permission' => 'settings.website_tracking'],
            ['path' => '/admin/settings/optimize', 'permission' => 'settings.optimize'],
            ['path' => '/admin/settings/blog', 'permission' => 'settings.blog'],
            ['path' => '/admin/settings/members', 'permission' => 'settings.member'],
            ['path' => '/admin/translations/locales', 'permission' => 'settings.localization.locales'],
            ['path' => '/admin/media', 'permission' => 'media.index'],
            ['path' => '/admin/request-logs', 'permission' => 'request_logs.index'],
        ];
    }
}
