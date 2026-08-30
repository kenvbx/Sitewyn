<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PDO;
use Sitewyn\Core\Base\Exceptions\PluginDependencyException;
use Sitewyn\Core\Base\Models\Plugin;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Sitewyn\Core\Base\Support\PluginActivator;
use Sitewyn\Core\Base\Support\PluginManager;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

/**
 * End-to-end lifecycle of the core packages that became manageable plugins
 * in P4-08 (page, blog), plus the P4-09 version-constraint gate on
 * "requires".
 *
 * The page/blog manifests gate their providers at boot time, so each test
 * boots against its own throwaway sqlite FILE — never :memory: — and drives
 * state through the real plugin:activate / plugin:deactivate commands,
 * re-booting via refreshApplication() after every state change.
 * RefreshDatabase is deliberately NOT used: its transaction wrapper would
 * hide boot-time state from the registration decisions, and its migrate
 * would fight the full-schema setup these tests need.
 *
 * Unlike PluginLifecycleTest the throwaway database is NOT pre-seeded with
 * a raw-PDO plugins table: the lifecycle tests start from a fully migrated
 * schema (like a real install) and let the commands write plugin rows
 * through the model. The constraint fixtures only need the plugins table,
 * so they create it directly.
 */
class PackagePluginLifecycleTest extends TestCase
{
    private const BLOG_MIGRATIONS = [
        '2026_08_30_000003_create_posts_table',
        '2026_08_30_000006_add_featured_image_to_posts_table',
        '2026_08_30_000008_add_og_image_to_posts_table',
    ];

    private string $databaseFile;

    /**
     * @var array<string, string|false>
     */
    private array $originalEnv = [];

    /**
     * @var array<int, string>
     */
    private array $fixtureBasePaths = [];

    protected function setUp(): void
    {
        $this->databaseFile = sys_get_temp_dir().'/sitewyn-package-lifecycle-'.uniqid().'.sqlite';

        foreach (['DB_CONNECTION', 'DB_DATABASE'] as $key) {
            $this->originalEnv[$key] = $_ENV[$key] ?? false;
        }

        touch($this->databaseFile);
        $this->useSqliteDatabase();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->fixtureBasePaths as $basePath) {
            $this->removeDirectory($basePath);
        }

        $this->fixtureBasePaths = [];

        if (is_file($this->databaseFile)) {
            @unlink($this->databaseFile);
        }

        foreach (['DB_CONNECTION', 'DB_DATABASE'] as $key) {
            if ($this->originalEnv[$key] === false) {
                unset($_ENV[$key], $_SERVER[$key]);
                putenv($key);
            } else {
                $_ENV[$key] = $this->originalEnv[$key];
                $_SERVER[$key] = $this->originalEnv[$key];
                putenv($key.'='.$this->originalEnv[$key]);
            }
        }
    }

    /**
     * (a) Blog, the fully worked example: deactivation unregisters the
     * provider (routes + admin menu disappear, public/admin requests 404)
     * while all post data stays in place; re-activation restores everything
     * without touching the data or re-running migrations.
     */
    public function test_blog_deactivation_removes_routes_and_menu_but_keeps_data_until_reactivation(): void
    {
        $this->migrateFullSchema();

        $post = Post::query()->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
            'content' => '<p>Post body copy</p>',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        // Healthy state: provider registered, public route serving.
        $this->assertNotNull(Route::getRoutes()->getByName('blog.posts.show'));
        $this->assertNotNull(Route::getRoutes()->getByName('admin.posts.index'));
        $this->get('/blog/hello-world')->assertOk();

        $this->artisan('plugin:deactivate', ['slug' => 'blog'])->assertExitCode(0);
        $this->refreshApplication();

        // Routes and menu entries are gone — the provider never registered.
        $this->assertNull(Route::getRoutes()->getByName('blog.posts.show'));
        $this->assertNull(Route::getRoutes()->getByName('admin.posts.index'));
        $this->assertNull(Route::getRoutes()->getByName('platform.package.blog.health'));

        $menuIds = $this->app->make(AdminMenuRegistry::class)->all()->pluck('id');

        $this->assertFalse($menuIds->contains('posts'));
        $this->assertFalse($menuIds->contains('categories'));
        $this->assertFalse($menuIds->contains('tags'));
        $this->assertTrue($menuIds->contains('pages'), 'The page plugin must be unaffected.');

        // Route not registered → the framework 404s both surfaces.
        $this->get('/blog/hello-world')->assertNotFound();
        $this->get('/admin/posts')->assertNotFound();

        // Data is kept — deactivation never deletes rows.
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'slug' => 'hello-world']);

        $this->artisan('plugin:activate', ['slug' => 'blog'])->assertExitCode(0);
        $this->refreshApplication();

        $this->assertNotNull(Route::getRoutes()->getByName('blog.posts.show'));
        $this->assertNotNull(Route::getRoutes()->getByName('admin.posts.index'));
        $this->get('/blog/hello-world')->assertOk();
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'slug' => 'hello-world']);
        $this->assertTrue(Plugin::query()->where('slug', 'blog')->firstOrFail()->is_active);

        foreach (self::BLOG_MIGRATIONS as $migration) {
            $this->assertSame(
                1,
                DB::table('migrations')->where('migration', $migration)->count(),
                "Re-activation must not re-run [{$migration}].",
            );
        }
    }

    /**
     * (a) Page: same lifecycle for the catch-all /{slug} plugin — one full
     * round trip is enough here, blog above carries the deep assertions.
     */
    public function test_page_deactivation_removes_routes_and_menu_but_keeps_data_until_reactivation(): void
    {
        $this->migrateFullSchema();

        $page = Page::query()->create([
            'title' => 'About us',
            'slug' => 'about-us',
            'content' => '<p>About body copy</p>',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        $this->assertNotNull(Route::getRoutes()->getByName('pages.show'));
        $this->get('/about-us')->assertOk();

        $this->artisan('plugin:deactivate', ['slug' => 'page'])->assertExitCode(0);
        $this->refreshApplication();

        $this->assertNull(Route::getRoutes()->getByName('pages.show'));
        $this->assertNull(Route::getRoutes()->getByName('admin.pages.index'));
        $this->assertNotContains('pages', $this->app->make(AdminMenuRegistry::class)->all()->pluck('id')->all());

        // The single-segment catch-all is gone → 404 on both surfaces.
        $this->get('/about-us')->assertNotFound();
        $this->get('/admin/pages')->assertNotFound();

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'slug' => 'about-us']);

        $this->artisan('plugin:activate', ['slug' => 'page'])->assertExitCode(0);
        $this->refreshApplication();

        $this->assertNotNull(Route::getRoutes()->getByName('pages.show'));
        $this->get('/about-us')->assertOk();
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'slug' => 'about-us']);
        $this->assertSame(
            1,
            DB::table('migrations')->where('migration', '2026_08_30_000001_create_pages_table')->count(),
        );
    }

    /**
     * (b) The migration flow behind a FIRST activation on an
     * already-migrated install (every pre-plugin-era database, dev included):
     * the scoped migrate re-run finds every page/blog migration already
     * recorded and executes nothing — activation is a pure state write.
     */
    public function test_first_activation_on_an_already_migrated_database_runs_no_migrations(): void
    {
        $this->migrateFullSchema();

        $this->assertDatabaseMissing('plugins', ['slug' => 'blog']);

        $this->artisan('plugin:activate', ['slug' => 'blog'])->assertExitCode(0);

        $this->assertDatabaseHas('plugins', ['slug' => 'blog', 'is_active' => true]);

        foreach (self::BLOG_MIGRATIONS as $migration) {
            $this->assertSame(
                1,
                DB::table('migrations')->where('migration', $migration)->count(),
                "First activation on a migrated database must not duplicate [{$migration}].",
            );
        }
    }

    /**
     * (c) P4-09 scan normalisation: the map form "requires": {"dep": "^1.0"}
     * surfaces as a slug list plus a separate constraints map, so the
     * dependency rules keep working on plain slugs.
     */
    public function test_scan_normalizes_map_form_requirements_into_slugs_and_constraints(): void
    {
        $basePath = $this->makeConstraintFixture('constrained', ['dep' => '^1.0'], '1.4.2');

        $manager = new PluginManager($basePath);

        $plugin = $manager->find('constrained');

        $this->assertNotNull($plugin);
        $this->assertSame(['dep'], $plugin['requires']);
        $this->assertSame(['dep' => '^1.0'], $plugin['constraints']);

        $dependency = $manager->find('dep');

        $this->assertSame([], $dependency['requires']);
        $this->assertSame([], $dependency['constraints']);
    }

    /**
     * (c) A satisfied caret constraint activates. Caret is deliberately a
     * narrow prefix match (P4-09): ^1.0 covers 1.0 itself and 1.0.x only —
     * not 1.4.2 — trading semver completeness for a dependency-free check.
     */
    public function test_activation_passes_with_a_satisfied_caret_constraint(): void
    {
        $basePath = $this->makeConstraintFixture('constrained', ['dep' => '^1.0'], '1.0.5');
        $activator = new PluginActivator(new PluginManager($basePath));

        $activator->activate('dep');
        $activator->activate('constrained');

        $this->assertDatabaseHas('plugins', ['slug' => 'dep', 'is_active' => true]);
        $this->assertDatabaseHas('plugins', ['slug' => 'constrained', 'is_active' => true]);
    }

    /**
     * (c) A different dependency version blocks activation — caret (^2.0
     * vs 1.4.2) and exact (2.0.0 vs 1.4.2) alike — while the already-active
     * dependency row is left untouched.
     */
    public function test_activation_is_blocked_when_the_installed_version_differs(): void
    {
        $activator = new PluginActivator(new PluginManager(
            $this->makeConstraintFixture('constrained', ['dep' => '^2.0'], '1.4.2'),
        ));

        $activator->activate('dep');

        try {
            $activator->activate('constrained');
            $this->fail('Expected the caret mismatch to block activation.');
        } catch (PluginDependencyException $exception) {
            $this->assertSame(
                'Plugin [constrained] requires [dep] ^2.0 but version [1.4.2] is installed. '
                .'Supported constraints: exact "1.2.3" or "^1.2".',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseMissing('plugins', ['slug' => 'constrained']);
        $this->assertDatabaseHas('plugins', ['slug' => 'dep', 'is_active' => true]);

        $activator = new PluginActivator(new PluginManager(
            $this->makeConstraintFixture('constrained', ['dep' => '2.0.0'], '1.4.2'),
        ));

        $activator->activate('dep');

        try {
            $activator->activate('constrained');
            $this->fail('Expected the exact-version mismatch to block activation.');
        } catch (PluginDependencyException $exception) {
            $this->assertSame(
                'Plugin [constrained] requires [dep] 2.0.0 but version [1.4.2] is installed. '
                .'Supported constraints: exact "1.2.3" or "^1.2".',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseMissing('plugins', ['slug' => 'constrained']);
    }

    /**
     * (c) Fail-closed: an unsupported constraint format (~1.0) never
     * matches, so it surfaces as an activation error naming the constraint
     * instead of being silently ignored.
     */
    public function test_unsupported_constraint_format_fails_closed(): void
    {
        $activator = new PluginActivator(new PluginManager(
            $this->makeConstraintFixture('constrained', ['dep' => '~1.0'], '1.0.5'),
        ));

        $activator->activate('dep');

        try {
            $activator->activate('constrained');
            $this->fail('Expected the unsupported constraint to block activation.');
        } catch (PluginDependencyException $exception) {
            $this->assertSame(
                'Plugin [constrained] requires [dep] ~1.0 but version [1.0.5] is installed. '
                .'Supported constraints: exact "1.2.3" or "^1.2".',
                $exception->getMessage(),
            );
        }
    }

    /**
     * (c) Backward compatibility: the P4-07 plain slug list stays
     * unconstrained — any dependency version activates.
     */
    public function test_bare_slug_requirements_still_activate_without_version_checks(): void
    {
        $activator = new PluginActivator(new PluginManager(
            $this->makeConstraintFixture('constrained', ['dep'], '9.9.9'),
        ));

        $activator->activate('dep');
        $activator->activate('constrained');

        $this->assertDatabaseHas('plugins', ['slug' => 'constrained', 'is_active' => true]);
    }

    /**
     * Full schema, like a real install: root + core + package + fixture
     * migrations against the throwaway sqlite file. This also creates the
     * real plugins table (no raw-PDO shortcut that would collide with it).
     */
    private function migrateFullSchema(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
    }

    /**
     * Plugins table only — enough for the activator's row bookkeeping; the
     * fixtures ship no migrations or providers.
     */
    private function createPluginsTable(): void
    {
        $pdo = new PDO('sqlite:'.$this->databaseFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE IF NOT EXISTS plugins (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            name VARCHAR NOT NULL,
            slug VARCHAR NOT NULL,
            version VARCHAR DEFAULT NULL,
            description VARCHAR DEFAULT NULL,
            is_active INTEGER NOT NULL DEFAULT 0,
            activated_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL
        )');
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS plugins_slug_unique ON plugins (slug)');
    }

    private function useSqliteDatabase(): void
    {
        foreach (['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $this->databaseFile] as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key.'='.$value);
        }
    }

    /**
     * Temp plugin root with a bare dependency and a constrained consumer.
     *
     * @param  array<int, string>|array<string, string>  $requires
     */
    private function makeConstraintFixture(string $slug, array $requires, string $dependencyVersion): string
    {
        $basePath = sys_get_temp_dir().'/sitewyn-package-lifecycle-fixture-'.uniqid();

        $this->writeManifest($basePath, 'dep', ['version' => $dependencyVersion]);
        $this->writeManifest($basePath, $slug, ['requires' => $requires]);

        $this->createPluginsTable();
        $this->fixtureBasePaths[] = $basePath;

        return $basePath;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function writeManifest(string $basePath, string $slug, array $extra): void
    {
        $directory = $basePath.'/platform/plugins/'.$slug;

        @mkdir($directory, 0777, true);

        file_put_contents($directory.'/plugin.json', json_encode(array_merge([
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'version' => '1.0.0',
        ], $extra)));
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
