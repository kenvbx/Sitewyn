<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Packages\Blog\Models\Category;
use Sitewyn\Packages\Blog\Models\Post;
use Tests\TestCase;

class AdminCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        putenv('CMS_ENABLED_CLEANUP_DATABASE');
        $_ENV['CMS_ENABLED_CLEANUP_DATABASE'] = null;
        $_SERVER['CMS_ENABLED_CLEANUP_DATABASE'] = null;

        parent::tearDown();
    }

    public function test_cleanup_route_uses_system_url_prefix(): void
    {
        $this->assertTrue(Route::has('admin.system.cleanup.index'));
        $this->assertSame('/admin/system/cleanup', route('admin.system.cleanup.index', [], false));
    }

    public function test_guest_is_redirected_from_cleanup_page(): void
    {
        $this->get('/admin/system/cleanup')->assertRedirect('/admin/login');
    }

    public function test_admin_without_permission_is_forbidden(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/system/cleanup')
            ->assertForbidden();
    }

    public function test_cleanup_page_lists_tables_and_is_disabled_by_default(): void
    {
        $content = $this->actingAs($this->userWithPermissions(['cleanup.manage']), 'admin')
            ->get('/admin/system/cleanup')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Please backup your database and script files before cleanup', $content);
        $this->assertStringContainsString('CMS_ENABLED_CLEANUP_DATABASE=true', $content);
        $this->assertStringContainsString('Please choose to ignore tables that do not want to be cleaned:', $content);
        $this->assertStringContainsString('users', $content);
        $this->assertStringContainsString('settings', $content);
        $this->assertStringContainsString('disabled', $content);
    }

    public function test_cleanup_post_is_blocked_when_feature_is_disabled(): void
    {
        Post::query()->create([
            'title' => 'Cleanup test post',
            'slug' => 'cleanup-test-post',
            'status' => Post::STATUS_DRAFT,
        ]);

        $this->actingAs($this->userWithPermissions(['cleanup.manage']), 'admin')
            ->post('/admin/system/cleanup', ['ignored_tables' => ['users']])
            ->assertRedirect('/admin/system/cleanup')
            ->assertSessionHas('status');

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_cleanup_clears_unchecked_tables_and_keeps_ignored_tables(): void
    {
        $this->enableCleanup();

        $user = $this->userWithPermissions(['cleanup.manage']);
        Category::query()->create([
            'name' => 'Cleanup category',
            'slug' => 'cleanup-category',
        ]);
        Post::query()->create([
            'title' => 'Cleanup test post',
            'slug' => 'cleanup-test-post',
            'status' => Post::STATUS_DRAFT,
        ]);

        $this->assertDatabaseCount('posts', 1);
        $this->assertDatabaseCount('categories', 1);

        $this->actingAs($user, 'admin')
            ->post('/admin/system/cleanup', [
                'ignored_tables' => [
                    'users',
                    'roles',
                    'role_user',
                    'permissions',
                    'settings',
                    'migrations',
                    'categories',
                ],
            ])
            ->assertRedirect('/admin/system/cleanup')
            ->assertSessionHas('status');

        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('categories', 1);
        $this->assertGreaterThan(0, DB::table('users')->count());
    }

    public function test_platform_hub_shows_cleanup_card_only_with_permission(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertDontSee('href="/admin/system/cleanup"', false);

        $this->actingAs($this->userWithPermissions(['cleanup.manage']), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertSee('href="/admin/system/cleanup"', false)
            ->assertSee('Cleanup your unused data in database.');
    }

    private function enableCleanup(): void
    {
        putenv('CMS_ENABLED_CLEANUP_DATABASE=true');
        $_ENV['CMS_ENABLED_CLEANUP_DATABASE'] = 'true';
        $_SERVER['CMS_ENABLED_CLEANUP_DATABASE'] = 'true';
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
            $role->permissions()->attach(Permission::query()->firstOrCreate(
                ['key' => $key],
                [
                    'name' => $key,
                    'module' => 'core/base',
                    'group' => str($key)->before('.')->toString(),
                    'description' => null,
                ],
            ));
        }

        $user->roles()->attach($role);

        return $user;
    }
}
