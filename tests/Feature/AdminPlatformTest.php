<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Tests\TestCase;

class AdminPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.platform'));
        $this->assertSame('/admin/platform', route('admin.platform', [], false));
    }

    public function test_guest_is_redirected_from_the_platform_hub(): void
    {
        $this->get('/admin/platform')->assertRedirect('/admin/login');
    }

    public function test_super_admin_sees_all_ten_tool_cards(): void
    {
        $content = $this->actingAs($this->adminUser(), 'admin')
            ->get('/admin/platform')
            ->assertOk()
            ->getContent();

        foreach ([
            'View and update your system users.',
            'View and update your roles and permissions.',
            'Browse every registered permission by module.',
            'Manage your media library files and folders.',
            'Build and organize your frontend navigation.',
            'Place content widgets into your theme areas.',
            'Activate or deactivate platform plugins.',
            'Review every recorded admin activity.',
            'Back up your database and uploads folder.',
            'Configure your site name, theme and options.',
        ] as $description) {
            $this->assertStringContainsString($description, $content);
        }

        $this->assertSame(10, substr_count($content, 'data-platform-card="'));
    }

    public function test_user_with_users_index_only_sees_the_users_card(): void
    {
        $content = $this->actingAs($this->userWithPermissions(['users.index']), 'admin')
            ->get('/admin/platform')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('View and update your system users.', $content);
        $this->assertStringNotContainsString('Back up your database and uploads folder.', $content);
        $this->assertStringNotContainsString('Activate or deactivate platform plugins.', $content);
        $this->assertSame(1, substr_count($content, 'data-platform-card="'));
    }

    public function test_admin_without_permissions_sees_the_empty_state(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/platform')
            ->assertOk()
            ->assertSee('No administration tools available for your account.')
            ->assertDontSee('data-platform-card="', false);
    }

    public function test_platform_sidebar_item_is_visible_for_every_admin(): void
    {
        $registry = $this->app->make(AdminMenuRegistry::class);

        $this->assertTrue($registry->visibleFor($this->adminUser())->pluck('id')->contains('platform'));
        $this->assertTrue($registry->visibleFor($this->plainAdmin())->pluck('id')->contains('platform'));

        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/platform')
            ->assertOk()
            ->assertSee('Platform Administration');
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
    }

    private function plainAdmin(): User
    {
        return User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = $this->plainAdmin();
        $role = Role::factory()->create();

        foreach ($permissions as $key) {
            $role->permissions()->attach(Permission::factory()->create([
                'key' => $key,
            ]));
        }

        $user->roles()->attach($role);

        return $user;
    }
}
