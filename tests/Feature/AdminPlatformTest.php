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

    public function test_super_admin_sees_all_eleven_tool_cards(): void
    {
        $content = $this->actingAs($this->adminUser(), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->getContent();

        foreach ([
            'View and update your system users.',
            'Manage non-team member accounts.',
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
        $this->assertStringContainsString('href="/admin/users"', $content);
    }

    /**
     * Team gating: the Users card no longer follows the users.index
     * permission — it only shows to team members, and a users.index-only
     * admin is not one. They do see the new Members card, which gates on
     * users.index and links to /admin/users.
     */
    public function test_user_with_users_index_only_sees_the_members_card(): void
    {
        $content = $this->actingAs($this->userWithPermissions(['users.index']), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('View and update your system users.', $content);
        $this->assertStringContainsString('Manage non-team member accounts.', $content);
        $this->assertSame(1, substr_count($content, 'data-platform-card="'));
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
        $this->assertStringNotContainsString('Back up your database and uploads folder.', $content);
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
