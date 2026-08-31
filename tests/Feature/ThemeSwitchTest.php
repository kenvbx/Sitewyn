<?php

namespace Tests\Feature;

use App\Models\User;
use Sitewyn\Core\Base\Support\SettingStore;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

/**
 * Theme switching (P5-02) end to end: the view finder location is chosen
 * while the app boots, so each boot must observe the desired active_theme
 * value. A throwaway sqlite FILE (not :memory:) keeps data across
 * refreshApplication() reboots — the same idea as PluginLifecycleTest's
 * boot-time state, but with the real migrations instead of raw PDO.
 */
class ThemeSwitchTest extends TestCase
{
    private string $databaseFile;

    /**
     * @var array<string, string|false>
     */
    private array $originalEnv = [];

    protected function setUp(): void
    {
        $this->databaseFile = sys_get_temp_dir().'/sitewyn-theme-switch-'.uniqid().'.sqlite';
        touch($this->databaseFile);

        foreach (['DB_CONNECTION', 'DB_DATABASE'] as $key) {
            $this->originalEnv[$key] = $_ENV[$key] ?? false;
        }

        foreach (['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $this->databaseFile] as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key.'='.$value);
        }

        parent::setUp();

        $this->artisan('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_file($this->databaseFile)) {
            @unlink($this->databaseFile);
        }

        foreach ($this->originalEnv as $key => $original) {
            if ($original === false) {
                unset($_ENV[$key], $_SERVER[$key]);
                putenv($key);
            } else {
                $_ENV[$key] = $original;
                $_SERVER[$key] = $original;
                putenv($key.'='.$original);
            }
        }
    }

    public function test_default_theme_renders_the_frontend(): void
    {
        $this->createPage();

        $this->get('/about-us')
            ->assertOk()
            ->assertSee('<h1>About us</h1>', false)
            ->assertDontSee('TEST-MARKER-VIEW');
    }

    public function test_switching_the_active_theme_swaps_the_frontend_view_and_back(): void
    {
        $this->createPage();

        $this->bootWithTheme('test-marker');

        $this->get('/about-us')
            ->assertOk()
            ->assertSee('TEST-MARKER-VIEW')
            ->assertSee('<h1>About us</h1>', false);

        // Switching back to the default theme removes the marker again.
        $this->bootWithTheme('default');

        $this->get('/about-us')
            ->assertOk()
            ->assertDontSee('TEST-MARKER-VIEW')
            ->assertSee('<p>About body copy</p>', false);
    }

    public function test_unknown_active_theme_setting_falls_back_to_the_default_theme(): void
    {
        $this->createPage();

        $this->bootWithTheme('ghost-theme');

        $this->get('/about-us')
            ->assertOk()
            ->assertSee('<h1>About us</h1>', false)
            ->assertDontSee('TEST-MARKER-VIEW');
    }

    public function test_settings_form_lists_themes_and_switches_the_frontend_end_to_end(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $this->createPage();

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('name="active_theme"', false)
            ->assertSee('Sitewyn Default')
            ->assertSee('Test Marker');

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings')
            ->put('/admin/settings', [
                'site_name' => 'Sitewyn Personal',
                'active_theme' => 'test-marker',
            ])
            ->assertRedirect('/admin/settings')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', [
            'key' => 'active_theme',
            'value' => 'test-marker',
        ]);

        // Re-boot so the boot-time theme resolution picks up the saved theme.
        $this->refreshApplication();

        $this->get('/about-us')
            ->assertOk()
            ->assertSee('TEST-MARKER-VIEW');

        // And back again through the same form.
        $admin = User::query()->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->put('/admin/settings', [
                'site_name' => 'Sitewyn Personal',
                'active_theme' => 'default',
            ])
            ->assertRedirect('/admin/settings');

        $this->refreshApplication();

        $this->get('/about-us')
            ->assertOk()
            ->assertDontSee('TEST-MARKER-VIEW');
    }

    public function test_the_home_route_renders_through_the_active_theme(): void
    {
        // No content: the default theme shows the empty state, the fixture
        // theme its own home marker.
        $this->get('/')
            ->assertOk()
            ->assertSee('No content yet. Sign in to the admin to create your first page or post.');

        $this->bootWithTheme('test-marker');

        $this->get('/')
            ->assertOk()
            ->assertSee('TEST-MARKER-HOME');

        $this->bootWithTheme('default');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('TEST-MARKER-HOME');
    }

    public function test_settings_reject_an_unknown_theme_slug(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings')
            ->put('/admin/settings', [
                'site_name' => 'Sitewyn Personal',
                'active_theme' => 'ghost-theme',
            ])
            ->assertRedirect('/admin/settings')
            ->assertSessionHasErrors(['active_theme']);

        $this->assertDatabaseMissing('settings', ['key' => 'active_theme']);
    }

    /**
     * Re-boot the app so the boot-time theme resolution reads the new
     * active_theme value from the file database.
     */
    private function bootWithTheme(string $slug): void
    {
        app(SettingStore::class)->setMany(['active_theme' => $slug]);

        $this->refreshApplication();
    }

    private function createPage(): Page
    {
        return Page::query()->create([
            'title' => 'About us',
            'slug' => 'about-us',
            'content' => '<p>About body copy</p>',
            'status' => Page::STATUS_PUBLISHED,
        ]);
    }
}
