<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Sitewyn\Core\Base\Models\Menu;
use Sitewyn\Core\Base\Models\MenuItem;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Models\Widget;
use Sitewyn\Core\Base\Support\ThemeManager;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

/**
 * Widget areas (P5-04): the theme.json declaration, the admin CRUD plus
 * reordering, and the default theme's footer rendering. Acceptance — a
 * widget shows up in the right theme position.
 */
class AdminWidgetTest extends TestCase
{
    use RefreshDatabase;

    private ?string $fixtureBasePath = null;

    protected function tearDown(): void
    {
        if ($this->fixtureBasePath !== null) {
            $this->removeDirectory($this->fixtureBasePath);
        }

        parent::tearDown();
    }

    // ---- schema ----------------------------------------------------------

    public function test_widgets_table_has_the_expected_columns_and_area_index(): void
    {
        $this->assertTrue(Schema::hasColumns('widgets', ['id', 'area_slug', 'type', 'data', 'order', 'created_at', 'updated_at']));

        $indexes = collect(Schema::getIndexes('widgets'));
        $this->assertTrue($indexes->contains(fn (array $index): bool => $index['columns'] === ['area_slug']));
    }

    // ---- theme manifest ---------------------------------------------------

    public function test_theme_manager_reads_widget_areas_from_the_manifest(): void
    {
        $this->fixtureBasePath = sys_get_temp_dir().'/sitewyn-widget-theme-'.uniqid();

        $this->makeFixtureTheme('themes/declared', json_encode([
            'name' => 'Declared',
            'slug' => 'declared',
            'version' => '1.0.0',
            'widget_areas' => [
                ['slug' => 'footer', 'name' => 'Footer widgets'],
                ['slug' => 'sidebar', 'name' => 'Sidebar'],
                // Malformed entries are dropped silently: no name, bad slug.
                ['slug' => 'orphan'],
                ['slug' => 'Uppercase Slug', 'name' => 'Bad'],
                // Duplicate slug — the first one wins.
                ['slug' => 'footer', 'name' => 'Dup footer'],
            ],
        ]));
        $this->makeFixtureTheme('themes/plain', json_encode([
            'name' => 'Plain',
            'slug' => 'plain',
            'version' => '1.0.0',
        ]));

        $manager = new ThemeManager($this->fixtureBasePath);

        $this->assertSame(
            [
                ['slug' => 'footer', 'name' => 'Footer widgets'],
                ['slug' => 'sidebar', 'name' => 'Sidebar'],
            ],
            $manager->find('declared')['widget_areas'],
        );
        $this->assertSame([], $manager->find('plain')['widget_areas']);
    }

    public function test_the_default_theme_declares_the_footer_area(): void
    {
        $this->assertSame(
            [['slug' => 'footer', 'name' => 'Footer widgets']],
            app(ThemeManager::class)->widgetAreas(),
        );
    }

    // ---- admin: area picker ------------------------------------------------

    public function test_index_lists_the_theme_areas_in_the_selector(): void
    {
        $this->actingAs($this->adminUser(), 'admin')
            ->get('/admin/widgets')
            ->assertOk()
            ->assertSee('Footer widgets')
            ->assertSee('(footer)', false)
            ->assertSee('No widgets in this area yet', false);
    }

    public function test_index_shows_an_empty_state_when_the_theme_declares_no_areas(): void
    {
        // A base path without any theme: the active theme is undiscoverable,
        // so the admin has no areas to manage.
        $this->fixtureBasePath = sys_get_temp_dir().'/sitewyn-widget-empty-'.uniqid();
        mkdir($this->fixtureBasePath, 0777, true);

        $this->app->singleton(ThemeManager::class, fn (): ThemeManager => new ThemeManager($this->fixtureBasePath));

        $this->actingAs($this->adminUser(), 'admin')
            ->get('/admin/widgets')
            ->assertOk()
            ->assertSee('Current theme does not declare widget areas', false);
    }

    // ---- admin: CRUD ------------------------------------------------------

    public function test_create_a_pages_widget_in_the_footer_area(): void
    {
        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/widgets', $this->payload([
                'type' => Widget::TYPE_PAGES,
                'data.title' => 'Explore the site',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.widgets.index', ['area' => 'footer']));

        $this->assertDatabaseHas('widgets', [
            'area_slug' => 'footer',
            'type' => Widget::TYPE_PAGES,
            'order' => 1,
        ]);

        $widget = Widget::query()->where('type', Widget::TYPE_PAGES)->first();
        $this->assertSame(['title' => 'Explore the site'], $widget->data);
    }

    public function test_create_a_recent_posts_widget_with_a_limit(): void
    {
        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/widgets', $this->payload([
                'type' => Widget::TYPE_RECENT_POSTS,
                'data.title' => 'Latest',
                'data.limit' => '7',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.widgets.index', ['area' => 'footer']));

        $widget = Widget::query()->where('type', Widget::TYPE_RECENT_POSTS)->first();

        $this->assertNotNull($widget);
        $this->assertSame(['title' => 'Latest', 'limit' => 7], $widget->data);
    }

    public function test_create_a_text_widget_with_content(): void
    {
        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/widgets', $this->payload([
                'type' => Widget::TYPE_TEXT,
                'data.title' => 'About',
                'data.content' => '<p>Free text body.</p>',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.widgets.index', ['area' => 'footer']));

        $widget = Widget::query()->where('type', Widget::TYPE_TEXT)->first();

        $this->assertNotNull($widget);
        $this->assertSame(['title' => 'About', 'content' => '<p>Free text body.</p>'], $widget->data);
    }

    public function test_store_rejects_an_area_the_theme_does_not_declare(): void
    {
        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/widgets', $this->payload([
                'area_slug' => 'sidebar',
            ]))->assertSessionHasErrors('area_slug');

        $this->assertDatabaseCount('widgets', 0);
    }

    public function test_store_rejects_unknown_widget_types(): void
    {
        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/widgets', $this->payload([
                'type' => 'weather',
            ]))
            ->assertSessionHasErrors('type');

        $this->assertDatabaseCount('widgets', 0);
    }

    public function test_store_rejects_recent_posts_without_a_limit(): void
    {
        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/widgets', $this->payload([
                'type' => Widget::TYPE_RECENT_POSTS,
                'data.limit' => '',
            ]))
            ->assertSessionHasErrors('data.limit');

        $this->assertDatabaseCount('widgets', 0);
    }

    public function test_store_rejects_text_without_content(): void
    {
        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/widgets', $this->payload([
                'type' => Widget::TYPE_TEXT,
                'data.content' => '',
            ]))
            ->assertSessionHasErrors('data.content');

        $this->assertDatabaseCount('widgets', 0);
    }

    public function test_edit_shows_the_widget_form_prefilled(): void
    {
        $widget = $this->createWidget([
            'type' => Widget::TYPE_RECENT_POSTS,
            'data' => ['title' => 'Latest news', 'limit' => 9],
        ]);

        $this->actingAs($this->adminUser(), 'admin')
            ->get('/admin/widgets/'.$widget->id.'/edit')
            ->assertOk()
            ->assertSee('Latest news')
            ->assertSee('value="9"', false);
    }

    public function test_update_changes_the_widget_data(): void
    {
        $widget = $this->createWidget([
            'type' => Widget::TYPE_PAGES,
            'data' => ['title' => 'Old heading'],
        ]);

        $this->actingAs($this->adminUser(), 'admin')
            ->put('/admin/widgets/'.$widget->id, $this->payload([
                'type' => Widget::TYPE_PAGES,
                'data.title' => 'New heading',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.widgets.index', ['area' => 'footer']));

        $this->assertSame(['title' => 'New heading'], $widget->fresh()->data);
    }

    public function test_destroy_removes_the_widget(): void
    {
        $widget = $this->createWidget();

        $this->actingAs($this->adminUser(), 'admin')
            ->delete('/admin/widgets/'.$widget->id)
            ->assertRedirect(route('admin.widgets.index', ['area' => 'footer']));

        $this->assertDatabaseMissing('widgets', ['id' => $widget->id]);
    }

    // ---- admin: reorder -----------------------------------------------------

    public function test_move_swaps_the_order_with_the_neighbour(): void
    {
        $first = $this->createWidget(['order' => 1]);
        $second = $this->createWidget(['order' => 2]);
        $third = $this->createWidget(['order' => 3]);

        // Move the second widget up one row.
        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/widgets/'.$second->id.'/move', ['direction' => 'up'])
            ->assertRedirect(route('admin.widgets.index', ['area' => 'footer']));

        $this->assertSame(2, $first->fresh()->order);
        $this->assertSame(1, $second->fresh()->order);
        $this->assertSame(3, $third->fresh()->order);

        // And back down.
        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/widgets/'.$second->id.'/move', ['direction' => 'down'])
            ->assertRedirect(route('admin.widgets.index', ['area' => 'footer']));

        $this->assertSame(1, $first->fresh()->order);
        $this->assertSame(2, $second->fresh()->order);
        $this->assertSame(3, $third->fresh()->order);
    }

    public function test_move_at_the_edges_is_a_no_op(): void
    {
        $first = $this->createWidget(['order' => 1]);
        $last = $this->createWidget(['order' => 2]);

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/widgets/'.$first->id.'/move', ['direction' => 'up'])
            ->assertRedirect(route('admin.widgets.index', ['area' => 'footer']));

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/widgets/'.$last->id.'/move', ['direction' => 'down'])
            ->assertRedirect(route('admin.widgets.index', ['area' => 'footer']));

        $this->assertSame(1, $first->fresh()->order);
        $this->assertSame(2, $last->fresh()->order);
    }

    public function test_move_rejects_unknown_directions(): void
    {
        $widget = $this->createWidget();

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/widgets/'.$widget->id.'/move', ['direction' => 'sideways'])
            ->assertSessionHasErrors('direction');
    }

    // ---- permissions --------------------------------------------------------

    public function test_guest_is_redirected_from_widget_routes(): void
    {
        $widget = $this->createWidget();

        $this->get('/admin/widgets')->assertRedirect('/admin/login');
        $this->get('/admin/widgets/create')->assertRedirect('/admin/login');
        $this->get('/admin/widgets/'.$widget->id.'/edit')->assertRedirect('/admin/login');
    }

    public function test_widget_routes_require_the_widgets_manage_permission(): void
    {
        $widget = $this->createWidget();

        $cases = [
            ['get', '/admin/widgets'],
            ['get', '/admin/widgets/create'],
            ['post', '/admin/widgets', $this->payload()],
            ['get', '/admin/widgets/'.$widget->id.'/edit'],
            ['put', '/admin/widgets/'.$widget->id, $this->payload()],
            ['post', '/admin/widgets/'.$widget->id.'/move', ['direction' => 'up']],
            ['delete', '/admin/widgets/'.$widget->id],
        ];

        foreach ($cases as $case) {
            [$method, $path] = $case;
            $data = $case[2] ?? [];

            $this->actingAs($this->plainAdmin(), 'admin')
                ->{$method}($path, $data)
                ->assertForbidden();
        }
    }

    public function test_users_with_widgets_manage_can_manage_widgets(): void
    {
        $widget = $this->createWidget();
        $user = $this->userWithWidgetsManage();

        $this->actingAs($user, 'admin')->get('/admin/widgets')->assertOk();
        $this->actingAs($user, 'admin')->get('/admin/widgets/create?area=footer')->assertOk();
        $this->actingAs($user, 'admin')->get('/admin/widgets/'.$widget->id.'/edit')->assertOk();
    }

    public function test_sidebar_shows_the_widgets_menu_item(): void
    {
        // The area page itself never links back to the index — the only
        // occurrence of that href is the sidebar menu entry.
        $this->actingAs($this->adminUser(), 'admin')
            ->get('/admin/widgets')
            ->assertOk()
            ->assertSee('href="'.route('admin.widgets.index').'"', false);
    }

    // ---- frontend rendering ---------------------------------------------------

    public function test_footer_renders_a_pages_widget_with_published_pages_only(): void
    {
        // A primary menu silences the automatic pages nav, so every page
        // link on the response comes from the widget, not the navigation.
        $this->createPrimaryMenu();
        $this->createWidget([
            'type' => Widget::TYPE_PAGES,
            'data' => ['title' => 'Explore'],
        ]);
        $this->createPage(['title' => 'About us', 'slug' => 'about-us', 'status' => Page::STATUS_PUBLISHED]);
        $this->createPage(['title' => 'Sneak peek', 'slug' => 'sneak-peek']);

        $this->get('/about-us')
            ->assertOk()
            ->assertSee('widget-area-footer', false)
            ->assertSee('Explore')
            ->assertSee('href="/about-us"', false)
            ->assertDontSee('href="/sneak-peek"', false);
    }

    public function test_footer_renders_recent_posts_limited_to_the_configured_count(): void
    {
        // Page detail renders no posts in the main column — the blog links
        // below can only come from the footer widget.
        $this->createPage(['title' => 'About us', 'slug' => 'about-us', 'status' => Page::STATUS_PUBLISHED]);
        $this->createWidget([
            'type' => Widget::TYPE_RECENT_POSTS,
            'data' => ['title' => 'Latest', 'limit' => 2],
        ]);
        $this->createPost(['title' => 'Old post', 'status' => Post::STATUS_PUBLISHED]);
        $this->createPost(['title' => 'Middle post', 'status' => Post::STATUS_PUBLISHED]);
        $this->createPost(['title' => 'Newest post', 'status' => Post::STATUS_PUBLISHED]);
        $this->createPost(['title' => 'Draft post']);

        $this->get('/about-us')
            ->assertOk()
            ->assertSee('Latest')
            ->assertSee('href="/blog/newest-post"', false)
            ->assertSee('href="/blog/middle-post"', false)
            // The limit and the draft cut the list down.
            ->assertDontSee('href="/blog/old-post"', false)
            ->assertDontSee('href="/blog/draft-post"', false);
    }

    public function test_footer_renders_text_widget_content_raw(): void
    {
        $this->createWidget([
            'type' => Widget::TYPE_TEXT,
            'data' => ['title' => 'About this site', 'content' => '<p>Trusted admin HTML.</p>'],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('About this site')
            ->assertSee('<p>Trusted admin HTML.</p>', false);
    }

    public function test_widgets_render_in_saved_order(): void
    {
        $this->createWidget([
            'type' => Widget::TYPE_PAGES,
            'data' => ['title' => 'First widget'],
            'order' => 1,
        ]);
        $this->createWidget([
            'type' => Widget::TYPE_TEXT,
            'data' => ['title' => 'Second widget', 'content' => '<p>Body.</p>'],
            'order' => 2,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['First widget', 'Second widget']);
    }

    public function test_empty_footer_area_keeps_the_original_footer_markup(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('site-footer-credit', false)
            ->assertDontSee('widget-area', false);
    }

    public function test_unknown_widget_types_are_skipped_silently(): void
    {
        DB::table('widgets')->insert([
            'area_slug' => 'footer',
            'type' => 'bogus',
            'data' => json_encode(['title' => 'Ghost widget']),
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Ghost widget')
            // The only widget was skipped, so even the area shell is absent.
            ->assertDontSee('widget-area', false)
            ->assertSee('site-footer-credit', false);
    }

    // ---- helpers ------------------------------------------------------------

    /**
     * The field set the admin form always posts, regardless of type.
     * Overrides use dot-style keys ("type", "data.title", "data.limit").
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        $payload = [
            'area_slug' => 'footer',
            'type' => Widget::TYPE_PAGES,
            'data' => [
                'title' => 'Widget title',
                'limit' => '',
                'content' => '',
            ],
        ];

        foreach ($overrides as $key => $value) {
            data_set($payload, $key, $value);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createWidget(array $attributes = []): Widget
    {
        return Widget::query()->create([
            'area_slug' => 'footer',
            'type' => Widget::TYPE_PAGES,
            'data' => ['title' => 'Untitled widget'],
            'order' => 1,
            ...$attributes,
        ]);
    }

    /**
     * A primary-location menu with one custom item — it replaces the
     * automatic published-pages nav so widget assertions stay unambiguous.
     */
    private function createPrimaryMenu(): Menu
    {
        $menu = Menu::query()->create([
            'name' => 'Primary menu',
            'slug' => 'primary-menu-'.uniqid(),
            'location' => Menu::LOCATION_PRIMARY,
        ]);

        MenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Home',
            'type' => 'custom',
            'url' => '/',
            'order' => 0,
        ]);

        return $menu;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPage(array $attributes = []): Page
    {
        $title = (string) ($attributes['title'] ?? 'Untitled');

        return Page::query()->create([
            'title' => $title,
            'slug' => $attributes['slug'] ?? Str::slug($title),
            'content' => '<p>Page body copy</p>',
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPost(array $attributes = []): Post
    {
        $title = (string) ($attributes['title'] ?? 'Untitled');

        return Post::query()->create([
            'title' => $title,
            'slug' => $attributes['slug'] ?? Str::slug($title),
            'content' => '<p>Post body copy</p>',
            ...$attributes,
        ]);
    }

    private function userWithWidgetsManage(): User
    {
        $user = $this->plainAdmin();
        $role = Role::factory()->create();
        $permission = Permission::query()->firstOrCreate(
            ['key' => 'widgets.manage'],
            [
                'name' => 'widgets.manage',
                'module' => 'core/base',
                'group' => 'widgets',
                'description' => null,
            ],
        );

        $role->permissions()->attach($permission);
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

    private function makeFixtureTheme(string $relativeDir, string $manifestJson): void
    {
        $directory = $this->fixtureBasePath.'/platform/'.$relativeDir;

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($directory.'/theme.json', $manifestJson);
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
