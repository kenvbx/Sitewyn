<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Exceptions\PluginDependencyException;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Plugin;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Sitewyn\Core\Base\Support\PermissionRegistry;
use Sitewyn\Core\Base\Support\PluginActivator;
use Sitewyn\Core\Base\Support\PluginManager;
use Tests\TestCase;

/**
 * Admin plugin management UI (P4-06) plus the full dependency rules
 * (P4-07) exercised through the shared PluginActivator service.
 *
 * The fixtures live in platform/plugins and are NOT composer-autoloaded,
 * but the admin.plugins.* routes are core routes: they do not depend on
 * plugin state, so plain RefreshDatabase CRUD-style tests are enough.
 * A missing plugins row counts as ACTIVE (the no-row default), so tests
 * arrange an inactive state by seeding rows through the Plugin model and
 * calling PluginManager::refresh().
 */
class AdminPluginManageTest extends TestCase
{
    use RefreshDatabase;

    public function test_plugins_manage_permission_is_registered(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);

        $this->assertTrue($registry->has('plugins.manage'));

        $permission = $registry->all()->firstWhere('key', 'plugins.manage');

        $this->assertSame('plugins', $permission['group']);
        $this->assertSame('core/base', $permission['module']);
    }

    public function test_plugins_sidebar_item_requires_plugins_manage_permission(): void
    {
        $registry = $this->app->make(AdminMenuRegistry::class);

        $this->assertTrue($registry->has('plugins'));

        $item = $registry->all()->firstWhere('id', 'plugins');

        $this->assertSame('plugins.manage', $item['permission']);
        $this->assertSame('plugin', $item['icon']);
        // Plugins sit just before Settings (order 90) to keep system-level
        // entries grouped at the bottom of the sidebar.
        $this->assertSame(85, $item['order']);

        $this->assertTrue(
            $registry->visibleFor($this->userWithPermissions(['plugins.manage']))->pluck('id')->contains('plugins'),
        );
        $this->assertFalse($registry->visibleFor($this->plainAdmin())->pluck('id')->contains('plugins'));
    }

    public function test_guest_cannot_view_plugins(): void
    {
        $this->get('/admin/plugins')
            ->assertRedirect('/admin/login');
    }

    public function test_plugin_routes_require_the_plugins_manage_permission(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/plugins')
            ->assertForbidden();

        $this->actingAs($this->plainAdmin(), 'admin')
            ->post('/admin/plugins/demo-dependant/activate')
            ->assertForbidden();

        $this->actingAs($this->plainAdmin(), 'admin')
            ->post('/admin/plugins/demo-dependant/deactivate')
            ->assertForbidden();
    }

    public function test_super_admin_sees_discovered_plugins_with_active_badges_and_deactivate_modals(): void
    {
        $this->actingAs($this->adminUser(), 'admin')
            ->get('/admin/plugins')
            ->assertOk()
            ->assertSee('navbar-vertical', false)
            ->assertSee('Demo Plugin')
            ->assertSee('demo-plugin')
            ->assertSee('Demo Dependant')
            ->assertSee('demo-dependant')
            ->assertSee('<span class="badge bg-azure-lt">plugin</span>', false)
            ->assertSee('<span class="badge bg-success-lt">Active</span>', false)
            ->assertSee('data-bs-target="#plugin-deactivate-demo-plugin"', false)
            ->assertSee('data-bs-target="#plugin-deactivate-demo-dependant"', false)
            ->assertDontSee('>Activate</button>', false);
    }

    public function test_index_shows_the_activate_button_for_inactive_plugins(): void
    {
        $this->seedInactivePlugin('demo-dependant', 'Demo Dependant');

        $this->actingAs($this->adminUser(), 'admin')
            ->get('/admin/plugins')
            ->assertOk()
            ->assertSee('<span class="badge bg-secondary-lt">Inactive</span>', false)
            ->assertSee('action="/admin/plugins/demo-dependant/activate"', false)
            ->assertSee('>Activate</button>', false)
            ->assertDontSee('data-bs-target="#plugin-deactivate-demo-dependant"', false)
            // demo-plugin stays active and keeps its confirm modal.
            ->assertSee('data-bs-target="#plugin-deactivate-demo-plugin"', false);
    }

    public function test_super_admin_can_activate_an_inactive_plugin_via_the_ui(): void
    {
        // demo-dependant requires demo-plugin, which is active by default
        // (no row counts as active) — so the activation must succeed.
        $this->seedInactivePlugin('demo-dependant', 'Demo Dependant');

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/plugins/demo-dependant/activate')
            ->assertRedirect(route('admin.plugins.index'))
            ->assertSessionHas('admin_flash.type', 'success')
            ->assertSessionHas('admin_flash.message', 'Plugin [Demo Dependant] activated.');

        $this->assertDatabaseHas('plugins', [
            'slug' => 'demo-dependant',
            'is_active' => true,
        ]);
    }

    public function test_activating_an_already_active_plugin_flashes_a_notice_and_writes_nothing(): void
    {
        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/plugins/demo-plugin/activate')
            ->assertRedirect(route('admin.plugins.index'))
            ->assertSessionHas('admin_flash.type', 'info')
            ->assertSessionHas('admin_flash.message', 'Plugin [Demo Plugin] is already active.');

        $this->assertDatabaseMissing('plugins', ['slug' => 'demo-plugin']);
    }

    public function test_deactivating_an_already_inactive_plugin_flashes_a_notice(): void
    {
        $this->seedInactivePlugin('demo-dependant', 'Demo Dependant');

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/plugins/demo-dependant/deactivate')
            ->assertRedirect(route('admin.plugins.index'))
            ->assertSessionHas('admin_flash.type', 'info')
            ->assertSessionHas('admin_flash.message', 'Plugin [Demo Dependant] is already inactive.');

        $this->assertDatabaseHas('plugins', [
            'slug' => 'demo-dependant',
            'is_active' => false,
        ]);
    }

    public function test_activation_of_an_unknown_slug_returns_404(): void
    {
        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/plugins/does-not-exist/activate')
            ->assertNotFound();
    }

    public function test_deactivation_of_an_unknown_slug_returns_404(): void
    {
        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/plugins/does-not-exist/deactivate')
            ->assertNotFound();
    }

    public function test_activation_is_blocked_while_the_dependency_is_inactive(): void
    {
        $this->seedInactivePlugin('demo-plugin', 'Demo Plugin');
        $this->seedInactivePlugin('demo-dependant', 'Demo Dependant');

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/plugins/demo-dependant/activate')
            ->assertRedirect(route('admin.plugins.index'))
            ->assertSessionHas('admin_flash.type', 'danger')
            ->assertSessionHas(
                'admin_flash.message',
                'Plugin [demo-dependant] requires [demo-plugin] which is inactive. Activate it first.',
            );

        $this->assertDatabaseHas('plugins', [
            'slug' => 'demo-dependant',
            'is_active' => false,
        ]);
    }

    public function test_deactivation_is_blocked_while_a_dependent_is_active(): void
    {
        // Both fixtures are active by default (no rows) — deactivating the
        // dependency demo-plugin must be blocked by demo-dependant.
        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/plugins/demo-plugin/deactivate')
            ->assertRedirect(route('admin.plugins.index'))
            ->assertSessionHas('admin_flash.type', 'danger')
            ->assertSessionHas(
                'admin_flash.message',
                'Plugin [demo-plugin] cannot be deactivated: required by active plugin(s): demo-dependant.',
            );

        $this->assertDatabaseMissing('plugins', ['slug' => 'demo-plugin']);
        $this->assertDatabaseMissing('plugins', ['slug' => 'demo-dependant']);
    }

    public function test_deactivation_succeeds_in_dependency_order(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->post('/admin/plugins/demo-dependant/deactivate')
            ->assertRedirect(route('admin.plugins.index'))
            ->assertSessionHas('admin_flash.type', 'success');

        $this->assertDatabaseHas('plugins', ['slug' => 'demo-dependant', 'is_active' => false]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/plugins/demo-plugin/deactivate')
            ->assertRedirect(route('admin.plugins.index'))
            ->assertSessionHas('admin_flash.type', 'success');

        $this->assertDatabaseHas('plugins', ['slug' => 'demo-plugin', 'is_active' => false]);
    }

    /**
     * A circular requires chain (A requires B, B requires A) can never be
     * activated bottom-up, so activation is rejected up-front with an
     * explicit message. The fixture plugins live in a throwaway temp dir
     * and are plugged in by rebinding the PluginManager singleton.
     */
    public function test_circular_dependency_blocks_activation_with_a_clear_error(): void
    {
        $basePath = $this->makeCircularFixture();

        try {
            $this->app->instance(PluginManager::class, new PluginManager($basePath));

            Plugin::query()->create(['name' => 'Circ A', 'slug' => 'circ-a', 'version' => '1.0.0', 'is_active' => false]);
            Plugin::query()->create(['name' => 'Circ B', 'slug' => 'circ-b', 'version' => '1.0.0', 'is_active' => false]);

            $this->actingAs($this->adminUser(), 'admin')
                ->post('/admin/plugins/circ-a/activate')
                ->assertRedirect(route('admin.plugins.index'))
                ->assertSessionHas('admin_flash.type', 'danger')
                ->assertSessionHas(
                    'admin_flash.message',
                    'Circular dependency detected: circ-a → circ-b → circ-a.',
                );

            $this->assertDatabaseHas('plugins', ['slug' => 'circ-a', 'is_active' => false]);
        } finally {
            $this->removeDirectory($basePath);
        }
    }

    /**
     * P4-07 through the shared service: requirements must be activated
     * bottom-up, and deactivation is blocked by the transitive closure of
     * active dependents (not just the direct level).
     */
    public function test_dependency_chain_is_enforced_transitively_for_activate_and_deactivate(): void
    {
        $basePath = $this->makeChainFixture();

        try {
            $this->app->instance(PluginManager::class, new PluginManager($basePath));

            foreach (['chain-a', 'chain-b', 'chain-c'] as $slug) {
                Plugin::query()->create(['name' => 'Chain '.strtoupper($slug), 'slug' => $slug, 'version' => '1.0.0', 'is_active' => false]);
            }

            $activator = $this->app->make(PluginActivator::class);

            try {
                $activator->activate('chain-a');
                $this->fail('Expected the activation of chain-a to be blocked.');
            } catch (PluginDependencyException $exception) {
                $this->assertSame(
                    'Plugin [chain-a] requires [chain-b] which is inactive. Activate it first.',
                    $exception->getMessage(),
                );
            }

            try {
                $activator->activate('chain-b');
                $this->fail('Expected the activation of chain-b to be blocked.');
            } catch (PluginDependencyException $exception) {
                $this->assertSame(
                    'Plugin [chain-b] requires [chain-c] which is inactive. Activate it first.',
                    $exception->getMessage(),
                );
            }

            // Requirements are never auto-activated: bottom-up works.
            $activator->activate('chain-c');
            $activator->activate('chain-b');
            $activator->activate('chain-a');

            foreach (['chain-a', 'chain-b', 'chain-c'] as $slug) {
                $this->assertDatabaseHas('plugins', ['slug' => $slug, 'is_active' => true]);
            }

            // Transitive dependents: chain-a → chain-b → chain-c.
            try {
                $activator->deactivate('chain-c');
                $this->fail('Expected the deactivation of chain-c to be blocked.');
            } catch (PluginDependencyException $exception) {
                $this->assertSame(
                    'Plugin [chain-c] cannot be deactivated: required by active plugin(s): chain-a, chain-b.',
                    $exception->getMessage(),
                );
            }

            try {
                $activator->deactivate('chain-b');
                $this->fail('Expected the deactivation of chain-b to be blocked.');
            } catch (PluginDependencyException $exception) {
                $this->assertSame(
                    'Plugin [chain-b] cannot be deactivated: required by active plugin(s): chain-a.',
                    $exception->getMessage(),
                );
            }

            // The only safe order is dependents first.
            $activator->deactivate('chain-a');
            $activator->deactivate('chain-b');
            $activator->deactivate('chain-c');

            foreach (['chain-a', 'chain-b', 'chain-c'] as $slug) {
                $this->assertDatabaseHas('plugins', ['slug' => $slug, 'is_active' => false]);
            }
        } finally {
            $this->removeDirectory($basePath);
        }
    }

    private function seedInactivePlugin(string $slug, string $name): void
    {
        Plugin::query()->create([
            'name' => $name,
            'slug' => $slug,
            'version' => '1.0.0',
            'is_active' => false,
        ]);

        $this->app->make(PluginManager::class)->refresh();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = $this->plainAdmin();
        $role = Role::factory()->create();

        foreach ($permissions as $key) {
            $permission = Permission::query()->firstOrCreate(
                ['key' => $key],
                [
                    'name' => $key,
                    'module' => 'core/base',
                    'group' => 'plugins',
                    'description' => null,
                ],
            );

            $role->permissions()->attach($permission);
        }

        $user->roles()->attach($role);

        return $user;
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
     * Temp plugin root with two plugins requiring each other.
     */
    private function makeCircularFixture(): string
    {
        return $this->makeFixture([
            'circ-a' => ['circ-b'],
            'circ-b' => ['circ-a'],
        ]);
    }

    /**
     * Temp plugin root with a three-plugin requires chain: a → b → c.
     */
    private function makeChainFixture(): string
    {
        return $this->makeFixture([
            'chain-a' => ['chain-b'],
            'chain-b' => ['chain-c'],
            'chain-c' => [],
        ]);
    }

    /**
     * @param  array<string, array<int, string>>  $requires
     */
    private function makeFixture(array $requires): string
    {
        $basePath = sys_get_temp_dir().'/sitewyn-plugin-manage-'.uniqid();

        foreach ($requires as $slug => $dependencies) {
            $directory = $basePath.'/platform/plugins/'.$slug;

            @mkdir($directory, 0777, true);

            file_put_contents($directory.'/plugin.json', json_encode([
                'name' => ucfirst(str_replace('-', ' ', $slug)),
                'slug' => $slug,
                'version' => '1.0.0',
                'requires' => $dependencies,
            ]));
        }

        return $basePath;
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (glob($directory.'/*') ?: [] as $item) {
            if (is_dir($item)) {
                $this->removeDirectory($item);
            } else {
                @unlink($item);
            }
        }

        @rmdir($directory);
    }
}
