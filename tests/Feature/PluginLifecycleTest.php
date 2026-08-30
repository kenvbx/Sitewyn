<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PDO;
use Sitewyn\Core\Base\Models\Plugin;
use Sitewyn\Core\Base\Support\ModuleProviderRepository;
use Sitewyn\Packages\Blog\Providers\BlogServiceProvider;
use Sitewyn\Packages\Media\Providers\MediaServiceProvider;
use Sitewyn\Packages\Page\Providers\PageServiceProvider;
use Sitewyn\Plugins\DemoPlugin\Providers\DemoPluginServiceProvider;
use Tests\TestCase;

/**
 * Plugin lifecycle tests (P4-03/P4-04/P4-05) against the real fixture
 * plugins in platform/plugins/.
 *
 * The fixtures are NOT in composer autoload: their provider class is loaded
 * from disk through the psr-4 map inside plugin.json (handled by
 * ModuleProviderRepository — the same mechanism composer.json modules use).
 *
 * Provider registration happens while the app boots, before any test code
 * runs — so each test boots against its own throwaway sqlite file with the
 * desired plugin rows already written via raw PDO. RefreshDatabase is
 * deliberately NOT used: its transaction wrapper would hide boot-time state
 * from the registration decisions.
 */
class PluginLifecycleTest extends TestCase
{
    private const DEMO_PROVIDER = DemoPluginServiceProvider::class;

    private const DEMO_MIGRATION = '2026_08_30_000010_create_demo_plugins_table';

    private string $databaseFile;

    /**
     * @var array<string, string|false>
     */
    private array $originalEnv = [];

    private ?PDO $pdo = null;

    protected function setUp(): void
    {
        $this->databaseFile = sys_get_temp_dir().'/sitewyn-plugin-lifecycle-'.uniqid().'.sqlite';

        foreach (['DB_CONNECTION', 'DB_DATABASE'] as $key) {
            $this->originalEnv[$key] = $_ENV[$key] ?? false;
        }

        $this->prepareThrowawayDatabase();
        $this->useSqliteDatabase();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->pdo = null;

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
     * (a) First activation runs the plugin's scoped migrations; deactivate
     * keeps the table and its data; re-activation does not migrate twice.
     */
    public function test_first_activation_runs_scoped_migrations_and_keeps_data_on_deactivation(): void
    {
        $this->artisan('plugin:activate', ['slug' => 'demo-plugin'])->assertExitCode(0);

        $plugin = Plugin::query()->where('slug', 'demo-plugin')->firstOrFail();

        $this->assertTrue($plugin->is_active);
        $this->assertNotNull($plugin->activated_at);
        $this->assertTrue(Schema::hasTable('demo_plugins'));
        $this->assertSame(1, DB::table('migrations')->where('migration', self::DEMO_MIGRATION)->count());

        DB::table('demo_plugins')->insert(['name' => 'legacy data']);

        // demo-dependant (active by default) requires demo-plugin, so it
        // must go first or the deactivation below gets blocked.
        $this->artisan('plugin:deactivate', ['slug' => 'demo-dependant'])->assertExitCode(0);
        $this->artisan('plugin:deactivate', ['slug' => 'demo-plugin'])->assertExitCode(0);

        $plugin = Plugin::query()->where('slug', 'demo-plugin')->firstOrFail();

        $this->assertFalse($plugin->is_active);
        $this->assertTrue(Schema::hasTable('demo_plugins'), 'Deactivation must keep plugin data.');
        $this->assertSame('legacy data', (string) DB::table('demo_plugins')->value('name'));

        $this->artisan('plugin:activate', ['slug' => 'demo-plugin'])->assertExitCode(0);

        $this->assertSame(
            1,
            DB::table('migrations')->where('migration', self::DEMO_MIGRATION)->count(),
            'Re-activation must not duplicate plugin migrations.',
        );
        $this->assertSame(1, DB::table('demo_plugins')->count(), 'Re-activation must keep existing data.');
    }

    /**
     * (b) A plugin required by an active plugin cannot be deactivated.
     */
    public function test_deactivation_is_blocked_while_an_active_plugin_requires_it(): void
    {
        $this->artisan('plugin:activate', ['slug' => 'demo-dependant'])->assertExitCode(0);
        $this->artisan('plugin:activate', ['slug' => 'demo-plugin'])->assertExitCode(0);

        $this->artisan('plugin:deactivate', ['slug' => 'demo-plugin'])->assertExitCode(1);

        $this->assertTrue(
            Plugin::query()->where('slug', 'demo-plugin')->firstOrFail()->is_active,
            'Blocked deactivation must not flip the row.',
        );
    }

    /**
     * (c) Activating an unknown slug fails.
     */
    public function test_activation_fails_for_unknown_slug(): void
    {
        $this->artisan('plugin:activate', ['slug' => 'does-not-exist'])->assertExitCode(1);

        $this->assertSame(0, Plugin::query()->count());
    }

    /**
     * (d) An activated plugin has its provider registered and its routes
     * loaded into a freshly booted app; re-activating a plugin whose row
     * already exists must not run migrations again.
     */
    public function test_activated_plugin_registers_provider_and_routes(): void
    {
        $this->seedPluginRow('demo-plugin', true);

        $this->refreshApplication();

        $this->assertArrayHasKey(self::DEMO_PROVIDER, $this->app->getLoadedProviders());
        $this->assertNotNull(Route::getRoutes()->getByName('demo-plugin.health'));

        $this->artisan('plugin:activate', ['slug' => 'demo-plugin'])->assertExitCode(0);

        $this->assertNotNull(Route::getRoutes()->getByName('demo-plugin.health'));
        $this->assertFalse(Schema::hasTable('demo_plugins'), 'Re-activation of an already-known plugin must not migrate.');
    }

    /**
     * (d) A deactivated plugin contributes nothing: no provider, no routes —
     * while the manifest-less modules keep registering. Activating it again
     * restores its row.
     */
    public function test_deactivated_plugin_provider_is_not_registered(): void
    {
        $this->seedPluginRow('demo-plugin', false);

        $this->refreshApplication();

        $this->assertArrayNotHasKey(self::DEMO_PROVIDER, $this->app->getLoadedProviders());
        $this->assertNull(Route::getRoutes()->getByName('demo-plugin.health'));
        $this->assertArrayHasKey(PageServiceProvider::class, $this->app->getLoadedProviders());

        $this->artisan('plugin:activate', ['slug' => 'demo-plugin'])->assertExitCode(0);

        $this->assertTrue(Plugin::query()->where('slug', 'demo-plugin')->firstOrFail()->is_active);
    }

    /**
     * (e) Regression guard: page/blog/media ship no manifest, so they must
     * register exactly as before the plugin era.
     */
    public function test_manifestless_modules_still_register(): void
    {
        $loaded = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(PageServiceProvider::class, $loaded);
        $this->assertArrayHasKey(BlogServiceProvider::class, $loaded);
        $this->assertArrayHasKey(MediaServiceProvider::class, $loaded);
        $this->assertNotNull(Route::getRoutes()->getByName('admin.pages.index'));
    }

    /**
     * P4-03 decision: a valid plugin.json manifest is the source of truth —
     * its provider replaces the composer.json provider list entirely.
     */
    public function test_manifest_provider_overrides_composer_providers(): void
    {
        $basePath = $this->makeModuleFixture(
            '/sitewyn-plugin-lifecycle-duo-'.uniqid(),
            'Sitewyn\Plugins\LifecycleDuo\Composer\ComposerProvider',
            'Sitewyn\Plugins\LifecycleDuo\Manifest\ManifestProvider',
        );

        try {
            $entries = (new ModuleProviderRepository($basePath))->providerEntries(['platform/plugins']);

            $this->assertSame(
                ['Sitewyn\Plugins\LifecycleDuo\Manifest\ManifestProvider'],
                array_column($entries, 'provider'),
            );
            $this->assertSame(['duo'], array_column($entries, 'slug'));
        } finally {
            $this->removeDirectory($basePath);
        }
    }

    /**
     * A manifest missing a required field is not a plugin: the module falls
     * back to composer.json behaviour and gains no slug (never gated).
     */
    public function test_invalid_manifest_falls_back_to_composer_providers(): void
    {
        $basePath = $this->makeModuleFixture(
            '/sitewyn-plugin-lifecycle-broken-'.uniqid(),
            'Sitewyn\Plugins\LifecycleBroken\Composer\BrokenFallbackProvider',
            'Sitewyn\Plugins\LifecycleBroken\Manifest\BrokenManifestProvider',
            validManifest: false,
        );

        try {
            $entries = (new ModuleProviderRepository($basePath))->providerEntries(['platform/plugins']);

            $this->assertSame(
                ['Sitewyn\Plugins\LifecycleBroken\Composer\BrokenFallbackProvider'],
                array_column($entries, 'provider'),
            );
            $this->assertSame([null], array_column($entries, 'slug'));
        } finally {
            $this->removeDirectory($basePath);
        }
    }

    /**
     * Fresh throwaway sqlite file: a plugins table (the real migration is
     * irrelevant pre-boot) plus a clean slate for plugin data.
     */
    private function prepareThrowawayDatabase(): void
    {
        touch($this->databaseFile);

        $this->pdo = new PDO('sqlite:'.$this->databaseFile);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS plugins (
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
        $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS plugins_slug_unique ON plugins (slug)');
        $this->pdo->exec('DELETE FROM plugins');
        $this->pdo->exec('DROP TABLE IF EXISTS demo_plugins');
        $this->pdo->exec('DROP TABLE IF EXISTS migrations');
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
     * Arrange boot-time plugin state: registration decisions are made while
     * the app boots, so the row must exist before booting (or the app must
     * be re-booted afterwards).
     */
    private function seedPluginRow(string $slug, bool $isActive): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO plugins (name, slug, version, is_active, activated_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
        );
        $statement->execute([
            'Demo Plugin',
            $slug,
            '1.0.0',
            $isActive ? 1 : 0,
            $isActive ? '2026-08-30 00:00:00' : null,
            '2026-08-30 00:00:00',
            '2026-08-30 00:00:00',
        ]);
    }

    /**
     * Temp module shipping BOTH a composer.json provider and a plugin.json
     * provider, each backed by a real class file.
     */
    private function makeModuleFixture(
        string $suffix,
        string $composerProvider,
        string $manifestProvider,
        bool $validManifest = true,
    ): string {
        $basePath = sys_get_temp_dir().$suffix;
        $modulePath = $basePath.'/platform/plugins/duo';
        $composerNamespace = substr($composerProvider, 0, (int) strrpos($composerProvider, '\\'));
        $manifestNamespace = substr($manifestProvider, 0, (int) strrpos($manifestProvider, '\\'));

        $this->writeProviderClass($modulePath, $composerProvider, 'src/Composer');
        $this->writeProviderClass($modulePath, $manifestProvider, 'src/Manifest');

        file_put_contents($modulePath.'/composer.json', json_encode([
            'name' => 'sitewyn/plugin-lifecycle-fixture',
            'autoload' => [
                'psr-4' => [
                    $composerNamespace.'\\' => 'src/Composer/',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [$composerProvider],
                ],
            ],
        ]));

        file_put_contents($modulePath.'/plugin.json', json_encode([
            'name' => 'Lifecycle Fixture',
            'slug' => 'duo',
            'version' => $validManifest ? '1.0.0' : null,
            'provider' => $manifestProvider,
            'autoload' => [
                'psr-4' => [
                    $manifestNamespace.'\\' => 'src/Manifest/',
                ],
            ],
        ]));

        return $basePath;
    }

    private function writeProviderClass(string $modulePath, string $fqcn, string $relativeDir): void
    {
        $namespace = substr($fqcn, 0, (int) strrpos($fqcn, '\\'));
        $shortName = substr($fqcn, (int) strrpos($fqcn, '\\') + 1);
        $directory = $modulePath.'/'.$relativeDir;

        @mkdir($directory, 0777, true);

        file_put_contents(
            $directory.'/'.$shortName.'.php',
            "<?php\n\nnamespace {$namespace};\n\nclass {$shortName} extends \\Illuminate\\Support\\ServiceProvider\n{\n}\n",
        );
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
