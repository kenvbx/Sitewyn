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

    public function test_system_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.system'));
        $this->assertSame('/admin/system', route('admin.system', [], false));
    }

    public function test_guest_is_redirected_from_the_system_hub(): void
    {
        $this->get('/admin/system')->assertRedirect('/admin/login');
    }

    public function test_super_admin_sees_all_platform_tool_cards(): void
    {
        $content = $this->actingAs($this->adminUser(), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->getContent();

        foreach ([
            'View and update your system users.',
            'View and update your roles and permissions.',
            'View and delete your system activity logs.',
            'View and delete your system request logs.',
            'Backup database and uploads folder.',
            'Set up automated background tasks to keep your website running smoothly.',
            'Manage cookie security and HTTP headers',
            'Clear cache to make your site up to date.',
            'Cleanup your unused data in database.',
            'All information about current system configuration.',
            'Update your system to the latest version.',
        ] as $description) {
            $this->assertStringContainsString($description, $content);
        }

        foreach ([
            'Browse every registered permission by module.',
            'Manage your media library files and folders.',
            'Build and organize your frontend navigation.',
            'Place content widgets into your theme areas.',
            'Activate or deactivate platform plugins.',
            'Configure your site name, theme and options.',
        ] as $description) {
            $this->assertStringNotContainsString($description, $content);
        }

        $this->assertSame(11, substr_count($content, 'data-platform-card="'));
    }

    public function test_users_card_links_to_the_team_surface(): void
    {
        $content = $this->actingAs($this->adminUser(), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->getContent();

        // Card URLs are rendered verbatim (no route() helper).
        $this->assertStringContainsString('href="/admin/system/users"', $content);
    }

    /**
     * Team gating: the Users card no longer follows the users.index
     * permission — it only shows to team members, and a users.index-only
     * admin is not one. Members management lives at /admin/users and is
     * deliberately not part of this hub.
     */
    public function test_user_with_users_index_only_sees_an_empty_hub(): void
    {
        $content = $this->actingAs($this->userWithPermissions(['users.index']), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('View and update your system users.', $content);
        $this->assertStringNotContainsString('Manage non-team member accounts.', $content);
        $this->assertStringContainsString('No administration tools available for your account.', $content);
    }

    public function test_user_with_the_admin_role_sees_the_users_card(): void
    {
        $user = $this->plainAdmin();
        $user->roles()->attach(Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]));

        $content = $this->actingAs($user, 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('View and update your system users.', $content);
        $this->assertStringNotContainsString('Backup database and uploads folder.', $content);
        $this->assertStringNotContainsString('Set up automated background tasks to keep your website running smoothly.', $content);
        $this->assertStringNotContainsString('Manage cookie security and HTTP headers', $content);
        $this->assertStringNotContainsString('Clear cache to make your site up to date.', $content);
        $this->assertStringNotContainsString('Cleanup your unused data in database.', $content);
        $this->assertStringNotContainsString('All information about current system configuration.', $content);
        $this->assertStringNotContainsString('Update your system to the latest version.', $content);
        $this->assertSame(1, substr_count($content, 'data-platform-card="'));
    }

    public function test_admin_without_permissions_sees_the_empty_state(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertSee('No administration tools available for your account.')
            ->assertDontSee('data-platform-card="', false);
    }

    public function test_system_sidebar_item_is_visible_for_every_admin(): void
    {
        $registry = $this->app->make(AdminMenuRegistry::class);

        $this->assertTrue($registry->visibleFor($this->adminUser())->pluck('id')->contains('system'));
        $this->assertTrue($registry->visibleFor($this->plainAdmin())->pluck('id')->contains('system'));

        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/system')
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
