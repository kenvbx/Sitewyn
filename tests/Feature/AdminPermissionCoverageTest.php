<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Sitewyn\Core\Base\Support\PermissionRegistry;
use Tests\TestCase;

class AdminPermissionCoverageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Meta-test: every route guarded by `permission:` middleware must use a
     * key registered in the PermissionRegistry. A route wired to an
     * unregistered key would 403 for every admin forever, because no role
     * can ever hold a permission that does not exist.
     */
    public function test_every_route_permission_key_is_registered(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);
        $checked = [];

        foreach (Route::getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (! is_string($middleware) || ! str_starts_with($middleware, 'permission:')) {
                    continue;
                }

                foreach (explode('|', substr($middleware, strlen('permission:'))) as $key) {
                    $key = trim($key);
                    $checked[] = $key;

                    $this->assertTrue(
                        $registry->has($key),
                        "Route [{$route->getName()}] (/{$route->uri()}) requires permission [{$key}] that is not registered.",
                    );
                }
            }
        }

        // Guard against a vacuous pass: if the module routes failed to load,
        // the loop above would check nothing and still be green.
        foreach (['page.index', 'post.index', 'category.index', 'tag.index'] as $key) {
            $this->assertContains($key, $checked, "The scan never saw [{$key}]; module routes may not have loaded.");
        }
    }

    /**
     * Meta-test: every admin menu item (and child) that declares a permission
     * must use a registered key, and each Page/Blog sidebar entry must guard
     * itself with its own index permission.
     */
    public function test_every_admin_menu_permission_is_registered(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);
        $menu = $this->app->make(AdminMenuRegistry::class);

        $items = $menu->all()->flatMap(fn (array $item): array => [$item, ...$item['children']]);

        foreach ($items as $item) {
            foreach ((array) ($item['permission'] ?? []) as $key) {
                $this->assertTrue(
                    $registry->has($key),
                    "Admin menu item [{$item['id']}] guards unregistered permission [{$key}].",
                );
            }
        }

        foreach (['pages' => 'page.index', 'posts' => 'post.index', 'categories' => 'category.index', 'tags' => 'tag.index'] as $id => $key) {
            $item = $menu->all()->firstWhere('id', $id);

            $this->assertNotNull($item, "Admin menu item [{$id}] is missing.");
            $this->assertSame($key, $item['permission'], "Admin menu item [{$id}] must guard itself with [{$key}].");
        }
    }
}
