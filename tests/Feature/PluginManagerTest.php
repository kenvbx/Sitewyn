<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Sitewyn\Core\Base\Models\Plugin;
use Sitewyn\Core\Base\Support\PluginManager;
use Sitewyn\Packages\Blog\Providers\BlogServiceProvider;
use Sitewyn\Packages\Page\Providers\PageServiceProvider;
use Tests\TestCase;

class PluginManagerTest extends TestCase
{
    use RefreshDatabase;

    private ?string $fixtureBasePath = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureBasePath = sys_get_temp_dir().'/sitewyn-plugin-fixture-'.uniqid();

        $this->makeFixturePlugin('plugins/alpha', $this->manifest([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'version' => '1.0.0',
            'description' => 'Alpha plugin.',
            'provider' => 'Sitewyn\Plugins\Alpha\Providers\AlphaServiceProvider',
            'requires' => ['beta'],
        ]));
        $this->makeFixturePlugin('packages/alpha', $this->manifest([
            'name' => 'Alpha Package Clone',
            'slug' => 'alpha',
            'version' => '9.9.9',
        ]));
        $this->makeFixturePlugin('plugins/broken', $this->manifest([
            'name' => 'Broken',
        ]));
        $this->makeFixturePlugin('packages/invalid-json', '{"name": broken');
        $this->makeFixturePlugin('plugins/no-manifest', null);
        $this->makeFixturePlugin('packages/beta', $this->manifest([
            'name' => 'Beta',
            'slug' => 'beta',
            'version' => '0.2.0',
            'description' => 'Beta package.',
        ]));
    }

    protected function tearDown(): void
    {
        if ($this->fixtureBasePath !== null) {
            $this->removeDirectory($this->fixtureBasePath);
        }

        parent::tearDown();
    }

    public function test_plugins_table_is_created_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('plugins'));
        $this->assertTrue(Schema::hasColumns('plugins', [
            'id',
            'name',
            'slug',
            'version',
            'description',
            'is_active',
            'activated_at',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_plugins_slug_has_unique_index(): void
    {
        $uniqueOnSlug = collect(Schema::getIndexes('plugins'))->contains(
            fn (array $index): bool => ($index['unique'] ?? false) && ($index['columns'] ?? []) === ['slug'],
        );

        $this->assertTrue($uniqueOnSlug);
    }

    public function test_plugins_columns_have_expected_defaults(): void
    {
        Plugin::query()->create(['name' => 'Demo', 'slug' => 'demo']);

        $plugin = Plugin::query()->where('slug', 'demo')->firstOrFail();

        $this->assertFalse($plugin->is_active);
        $this->assertNull($plugin->activated_at);
        $this->assertNull($plugin->version);
        $this->assertNull($plugin->description);
    }

    public function test_scan_finds_valid_manifests_across_both_sources(): void
    {
        $manager = new PluginManager($this->fixtureBasePath);

        $this->assertInstanceOf(Collection::class, $manager->all());
        $this->assertSame(['alpha', 'beta'], $manager->availableSlugs());

        $alpha = $manager->find('alpha');

        $this->assertNotNull($alpha);
        $this->assertSame('Alpha', $alpha['name']);
        $this->assertSame('alpha', $alpha['slug']);
        $this->assertSame('1.0.0', $alpha['version']);
        $this->assertSame('Alpha plugin.', $alpha['description']);
        $this->assertSame('Sitewyn\Plugins\Alpha\Providers\AlphaServiceProvider', $alpha['provider']);
        $this->assertSame(['beta'], $alpha['requires']);
        $this->assertSame('plugin', $alpha['source']);
        $this->assertTrue($alpha['isActive']);
        $this->assertSame($this->fixtureBasePath.'/platform/plugins/alpha', $alpha['path']);

        $beta = $manager->find('beta');

        $this->assertNotNull($beta);
        $this->assertSame('package', $beta['source']);
        $this->assertSame($this->fixtureBasePath.'/platform/packages/beta', $beta['path']);
    }

    public function test_duplicate_slug_prefers_plugin_source(): void
    {
        $manager = new PluginManager($this->fixtureBasePath);

        $alphaEntries = $manager->all()->where('slug', 'alpha')->values();

        $this->assertCount(1, $alphaEntries);
        $this->assertSame('plugin', $alphaEntries[0]['source']);
        $this->assertSame('1.0.0', $alphaEntries[0]['version']);
    }

    public function test_invalid_or_missing_manifests_are_skipped(): void
    {
        $manager = new PluginManager($this->fixtureBasePath);

        $this->assertNull($manager->find('broken'));
        $this->assertNull($manager->find('invalid-json'));
        $this->assertNull($manager->find('no-manifest'));
        $this->assertSame(['alpha', 'beta'], $manager->availableSlugs());
    }

    public function test_find_returns_null_for_unknown_slug(): void
    {
        $manager = new PluginManager($this->fixtureBasePath);

        $this->assertNull($manager->find('missing'));
    }

    /**
     * P4-08: page/blog ship plugin.json manifests, so the real scan now
     * discovers them as manageable plugins (source: package). They stay
     * active by default — no plugins row counts as active — and media
     * remains manifest-less (a P5 decision, still not listed).
     */
    public function test_page_and_blog_are_discovered_as_manageable_package_plugins(): void
    {
        $manager = new PluginManager;

        $this->assertContains('blog', $manager->availableSlugs());
        $this->assertContains('page', $manager->availableSlugs());
        $this->assertNotContains('media', $manager->availableSlugs());

        $page = $manager->find('page');

        $this->assertNotNull($page);
        $this->assertSame('Pages', $page['name']);
        $this->assertSame('page', $page['slug']);
        $this->assertSame('1.0.0', $page['version']);
        $this->assertSame(PageServiceProvider::class, $page['provider']);
        $this->assertSame([], $page['requires']);
        $this->assertSame([], $page['constraints']);
        $this->assertSame('package', $page['source']);
        $this->assertSame(base_path('platform/packages/page'), $page['path']);
        $this->assertTrue($page['isActive']);

        $blog = $manager->find('blog');

        $this->assertNotNull($blog);
        $this->assertSame('Blog', $blog['name']);
        $this->assertSame(BlogServiceProvider::class, $blog['provider']);
        $this->assertSame([], $blog['requires']);
        $this->assertSame('package', $blog['source']);
        $this->assertTrue($blog['isActive']);
    }

    public function test_plugin_without_row_is_active_by_default(): void
    {
        $manager = new PluginManager($this->fixtureBasePath);

        $this->assertTrue($manager->isActive('alpha'));
        $this->assertContains('alpha', $manager->activeSlugs());
        $this->assertContains('beta', $manager->activeSlugs());
    }

    public function test_deactivated_row_marks_plugin_inactive(): void
    {
        Plugin::query()->create(['name' => 'Alpha', 'slug' => 'alpha', 'is_active' => false]);

        $manager = new PluginManager($this->fixtureBasePath);

        $this->assertFalse($manager->isActive('alpha'));
        $this->assertFalse($manager->find('alpha')['isActive']);
        $this->assertNotContains('alpha', $manager->activeSlugs());
        $this->assertTrue($manager->isActive('beta'));
    }

    public function test_activated_row_keeps_plugin_active(): void
    {
        Plugin::query()->create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $manager = new PluginManager($this->fixtureBasePath);

        $this->assertTrue($manager->isActive('alpha'));
        $this->assertContains('alpha', $manager->activeSlugs());
    }

    private function makeFixturePlugin(string $relativeDir, ?string $manifestJson): void
    {
        $directory = $this->fixtureBasePath.'/platform/'.$relativeDir;

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if ($manifestJson !== null) {
            file_put_contents($directory.'/plugin.json', $manifestJson);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function manifest(array $data): string
    {
        return (string) json_encode($data);
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
