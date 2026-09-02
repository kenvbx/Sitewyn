<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Tests\TestCase;

class AdminCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_cache_route_uses_system_url_prefix(): void
    {
        $this->assertTrue(Route::has('admin.system.cache.index'));
        $this->assertSame('/admin/system/cache', route('admin.system.cache.index', [], false));
    }

    public function test_guest_is_redirected_from_cache_page(): void
    {
        $this->get('/admin/system/cache')->assertRedirect('/admin/login');
    }

    public function test_admin_without_permission_is_forbidden(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/system/cache')
            ->assertForbidden();
    }

    public function test_admin_with_permission_can_view_cache_management(): void
    {
        $content = $this->actingAs($this->userWithPermissions(['settings.cache']), 'admin')
            ->get('/admin/system/cache')
            ->assertOk()
            ->getContent();

        foreach ([
            'Cache Management',
            'Clear cache to make your site up to date.',
            'Clear all CMS cache',
            'Current Size:',
            'Refresh compiled views',
            'Clear config cache',
            'Clear route cache',
            'Clear log',
            'Performance Optimization',
            'Optimize site performance',
            'Clear optimization cache',
        ] as $text) {
            $this->assertStringContainsString($text, $content);
        }
    }

    public function test_clear_cms_cache_flushes_application_cache(): void
    {
        Cache::put('cms-cache-test', 'cached');

        $this->actingAs($this->userWithPermissions(['settings.cache']), 'admin')
            ->post('/admin/system/cache/clear-cms')
            ->assertRedirect('/admin/system/cache')
            ->assertSessionHas('status');

        $this->assertFalse(Cache::has('cms-cache-test'));
    }

    public function test_clear_logs_empties_log_files(): void
    {
        $path = storage_path('logs/cache-management-test.log');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, 'log body');

        $this->actingAs($this->userWithPermissions(['settings.cache']), 'admin')
            ->post('/admin/system/cache/clear-logs')
            ->assertRedirect('/admin/system/cache')
            ->assertSessionHas('status');

        $this->assertSame('', File::get($path));

        File::delete($path);
    }

    public function test_cache_operations_are_registered(): void
    {
        $this->actingAs($this->userWithPermissions(['settings.cache']), 'admin')
            ->post('/admin/system/cache/refresh-views')
            ->assertRedirect('/admin/system/cache')
            ->assertSessionHas('status');

        $this->actingAs($this->userWithPermissions(['settings.cache']), 'admin')
            ->post('/admin/system/cache/clear-config')
            ->assertRedirect('/admin/system/cache')
            ->assertSessionHas('status');

        $this->actingAs($this->userWithPermissions(['settings.cache']), 'admin')
            ->post('/admin/system/cache/clear-routes')
            ->assertRedirect('/admin/system/cache')
            ->assertSessionHas('status');
    }

    public function test_platform_hub_shows_cache_card_only_with_permission(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertDontSee('href="/admin/system/cache"', false);

        $this->actingAs($this->userWithPermissions(['settings.cache']), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertSee('href="/admin/system/cache"', false)
            ->assertSee('Clear cache to make your site up to date.');
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
