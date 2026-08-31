<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Sitewyn\Core\Base\Support\SettingStore;
use Sitewyn\Core\Base\Support\ThemeManager;
use Tests\TestCase;

class ThemeManagerTest extends TestCase
{
    use RefreshDatabase;

    private ?string $fixtureBasePath = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureBasePath = sys_get_temp_dir().'/sitewyn-theme-fixture-'.uniqid();

        $this->makeFixtureTheme('themes/alpha', $this->manifest([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'version' => '2.3.4',
            'description' => 'Alpha theme.',
            'author' => 'Alpha Author',
        ]));
        $this->makeFixtureTheme('themes/default', $this->manifest([
            'name' => 'Fixture Default',
            'slug' => 'default',
            'version' => '1.0.0',
        ]));
        $this->makeFixtureTheme('themes/broken', $this->manifest([
            'name' => 'Broken',
            'slug' => 'broken',
        ]));
        $this->makeFixtureTheme('themes/invalid-json', '{"name": broken');
        $this->makeFixtureTheme('themes/no-manifest', null);
    }

    protected function tearDown(): void
    {
        if ($this->fixtureBasePath !== null) {
            $this->removeDirectory($this->fixtureBasePath);
        }

        parent::tearDown();
    }

    public function test_scan_finds_valid_theme_manifests_sorted_by_slug(): void
    {
        $manager = new ThemeManager($this->fixtureBasePath);

        $this->assertInstanceOf(Collection::class, $manager->all());
        $this->assertSame(['alpha', 'default'], $manager->availableSlugs());

        $alpha = $manager->find('alpha');

        $this->assertNotNull($alpha);
        $this->assertSame('Alpha', $alpha['name']);
        $this->assertSame('alpha', $alpha['slug']);
        $this->assertSame('2.3.4', $alpha['version']);
        $this->assertSame('Alpha theme.', $alpha['description']);
        $this->assertSame('Alpha Author', $alpha['author']);
        $this->assertSame($this->fixtureBasePath.'/platform/themes/alpha', $alpha['path']);
    }

    public function test_invalid_or_missing_manifests_are_skipped(): void
    {
        $manager = new ThemeManager($this->fixtureBasePath);

        $this->assertNull($manager->find('broken'));
        $this->assertNull($manager->find('invalid-json'));
        $this->assertNull($manager->find('no-manifest'));
        $this->assertSame(['alpha', 'default'], $manager->availableSlugs());
    }

    public function test_find_returns_null_for_unknown_slug(): void
    {
        $manager = new ThemeManager($this->fixtureBasePath);

        $this->assertNull($manager->find('missing'));
    }

    public function test_real_scan_discovers_the_default_and_test_marker_themes(): void
    {
        $manager = new ThemeManager;

        $this->assertContains('default', $manager->availableSlugs());
        $this->assertContains('test-marker', $manager->availableSlugs());

        $default = $manager->find('default');

        $this->assertNotNull($default);
        $this->assertSame('Sitewyn Default', $default['name']);
        $this->assertSame('default', $default['slug']);
        $this->assertSame('1.0.0', $default['version']);
        $this->assertSame('Sitewyn', $default['author']);
        $this->assertNotNull($default['description']);
        $this->assertSame(base_path('platform/themes/default'), $default['path']);
    }

    public function test_active_theme_defaults_to_the_default_theme(): void
    {
        $manager = new ThemeManager($this->fixtureBasePath);

        $this->assertSame('default', $manager->activeTheme()['slug']);
        $this->assertSame('Fixture Default', $manager->activeTheme()['name']);
    }

    public function test_active_theme_returns_the_theme_named_by_the_setting(): void
    {
        app(SettingStore::class)->setMany(['active_theme' => 'alpha']);

        $manager = new ThemeManager($this->fixtureBasePath);

        $this->assertSame('alpha', $manager->activeTheme()['slug']);
    }

    public function test_active_theme_falls_back_to_default_when_the_setting_theme_is_missing(): void
    {
        app(SettingStore::class)->setMany(['active_theme' => 'ghost-theme']);

        $manager = new ThemeManager($this->fixtureBasePath);

        $this->assertSame('default', $manager->activeTheme()['slug']);
    }

    public function test_active_theme_is_empty_when_even_the_default_theme_is_missing(): void
    {
        $basePath = sys_get_temp_dir().'/sitewyn-theme-empty-'.uniqid();
        $this->makeFixtureTheme('themes/alpha', $this->manifest([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'version' => '1.0.0',
        ]), $basePath);

        try {
            $manager = new ThemeManager($basePath);

            $this->assertSame([], $manager->activeTheme());
        } finally {
            $this->removeDirectory($basePath);
        }
    }

    private function makeFixtureTheme(string $relativeDir, ?string $manifestJson, ?string $basePath = null): void
    {
        $directory = ($basePath ?? $this->fixtureBasePath).'/platform/'.$relativeDir;

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if ($manifestJson !== null) {
            file_put_contents($directory.'/theme.json', $manifestJson);
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
