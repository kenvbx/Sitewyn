<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Sitewyn\Core\Base\Models\Menu;
use Sitewyn\Core\Base\Models\MenuItem;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

/**
 * Frontend navigation menus (P5-03): admin CRUD, the drag-and-drop
 * builder's replace-all item save, and the default theme's primary nav
 * rendering with its published-pages fallback.
 */
class AdminMenuTest extends TestCase
{
    use RefreshDatabase;

    // ---- schema ----------------------------------------------------------

    public function test_menu_tables_have_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('menus', ['id', 'name', 'slug', 'location', 'created_at', 'updated_at']));
        $this->assertTrue(Schema::hasColumns('menu_items', ['id', 'menu_id', 'parent_id', 'label', 'type', 'target_id', 'url', 'order', 'created_at', 'updated_at']));

        $menuIndexes = collect(Schema::getIndexes('menus'));
        $this->assertTrue($menuIndexes->contains(fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['slug']));

        $itemIndexes = collect(Schema::getIndexes('menu_items'));
        $this->assertTrue($itemIndexes->contains(fn (array $index): bool => collect($index['columns'])->sort()->values()->all() === ['menu_id', 'order']));
    }

    public function test_deleting_a_menu_cascades_its_items(): void
    {
        $menu = $this->createMenu();

        $this->createItem($menu, ['label' => 'First', 'type' => 'custom', 'url' => '/first', 'order' => 0]);
        $this->createItem($menu, ['label' => 'Second', 'type' => 'custom', 'url' => '/second', 'order' => 1]);

        $menu->delete();

        $this->assertDatabaseCount('menu_items', 0);
    }

    // ---- menu CRUD -------------------------------------------------------

    public function test_store_generates_a_slug_from_the_name_and_opens_the_builder(): void
    {
        $response = $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/menus', [
                'name' => 'Main navigation',
                'location' => 'primary',
            ]);

        $menu = Menu::query()->where('slug', 'main-navigation')->first();

        $this->assertNotNull($menu);
        $this->assertSame('primary', $menu->location);
        $response->assertRedirect(route('admin.menus.edit-items', $menu));
    }

    public function test_store_suffixes_a_duplicated_slug(): void
    {
        $this->createMenu(['name' => 'Main', 'slug' => 'main']);

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/menus', [
                'name' => 'Main',
                'slug' => 'main',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('menus', ['slug' => 'main-2']);
    }

    public function test_store_rejects_unknown_locations(): void
    {
        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/menus', [
                'name' => 'Footer menu',
                'location' => 'footer',
            ])
            ->assertSessionHasErrors('location');

        $this->assertDatabaseMissing('menus', ['name' => 'Footer menu']);
    }

    public function test_claiming_a_location_releases_the_previous_menu(): void
    {
        $previous = $this->createMenu(['name' => 'Old primary', 'location' => 'primary']);

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/menus', [
                'name' => 'New primary',
                'location' => 'primary',
            ])
            ->assertRedirect();

        $this->assertNull($previous->fresh()->location);
        $this->assertSame('primary', Menu::query()->where('name', 'New primary')->value('location'));
    }

    public function test_update_keeps_the_current_slug_when_left_empty(): void
    {
        $menu = $this->createMenu(['name' => 'Main', 'slug' => 'main']);

        $this->actingAs($this->adminUser(), 'admin')
            ->put('/admin/menus/'.$menu->id, [
                'name' => 'Renamed menu',
                'slug' => '',
            ])
            ->assertRedirect(route('admin.menus.index'));

        $menu = $menu->fresh();

        $this->assertSame('Renamed menu', $menu->name);
        $this->assertSame('main', $menu->slug);
    }

    public function test_destroy_removes_the_menu_and_its_items(): void
    {
        $menu = $this->createMenu();

        $this->createItem($menu, ['label' => 'Only', 'type' => 'custom', 'url' => '/only', 'order' => 0]);

        $this->actingAs($this->adminUser(), 'admin')
            ->delete('/admin/menus/'.$menu->id)
            ->assertRedirect(route('admin.menus.index'));

        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
        $this->assertDatabaseCount('menu_items', 0);
    }

    // ---- permissions -------------------------------------------------------

    public function test_guest_is_redirected_from_menu_routes(): void
    {
        $menu = $this->createMenu();

        $this->get('/admin/menus')->assertRedirect('/admin/login');
        $this->get('/admin/menus/'.$menu->id.'/edit-items')->assertRedirect('/admin/login');
    }

    public function test_menu_routes_require_the_menus_manage_permission(): void
    {
        $menu = $this->createMenu();

        $cases = [
            ['get', '/admin/menus'],
            ['get', '/admin/menus/create'],
            ['post', '/admin/menus', ['name' => 'Blocked menu']],
            ['get', '/admin/menus/'.$menu->id.'/edit-items'],
            ['post', '/admin/menus/'.$menu->id.'/items', ['items' => []]],
            ['get', '/admin/menus/'.$menu->id.'/edit'],
            ['put', '/admin/menus/'.$menu->id, ['name' => 'Blocked update']],
            ['delete', '/admin/menus/'.$menu->id],
        ];

        foreach ($cases as $case) {
            [$method, $path] = $case;
            $data = $case[2] ?? [];

            $this->actingAs($this->plainAdmin(), 'admin')
                ->{$method}($path, $data)
                ->assertForbidden();
        }
    }

    public function test_users_with_menus_manage_can_use_the_builder(): void
    {
        $menu = $this->createMenu();

        $user = $this->userWithMenusManage();

        $this->actingAs($user, 'admin')->get('/admin/menus')->assertOk();
        $this->actingAs($user, 'admin')->get('/admin/menus/create')->assertOk();
        $this->actingAs($user, 'admin')->get('/admin/menus/'.$menu->id.'/edit-items')->assertOk();
        $this->actingAs($user, 'admin')->get('/admin/menus/'.$menu->id.'/edit')->assertOk();
    }

    // ---- builder item save --------------------------------------------------

    public function test_store_items_replaces_the_whole_structure(): void
    {
        $menu = $this->createMenu();

        $this->createItem($menu, ['label' => 'Old one', 'type' => 'custom', 'url' => '/old-one', 'order' => 0]);
        $this->createItem($menu, ['label' => 'Old two', 'type' => 'custom', 'url' => '/old-two', 'order' => 1]);
        $this->createItem($menu, ['label' => 'Old three', 'type' => 'custom', 'url' => '/old-three', 'order' => 2]);

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/menus/'.$menu->id.'/items', [
                'items' => [
                    ['id' => 'n1', 'label' => 'New one', 'type' => 'custom', 'url' => '/new-one'],
                    ['id' => 'n2', 'label' => 'New two', 'type' => 'custom', 'url' => '/new-two'],
                ],
            ])
            ->assertRedirect(route('admin.menus.edit-items', $menu))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('menu_items', 2);
        $this->assertDatabaseHas('menu_items', ['menu_id' => $menu->id, 'label' => 'New one', 'order' => 0]);
        $this->assertDatabaseHas('menu_items', ['menu_id' => $menu->id, 'label' => 'New two', 'order' => 1]);
    }

    public function test_store_items_persists_nesting_and_order(): void
    {
        $page = $this->createPage(['title' => 'About us', 'slug' => 'about-us', 'status' => Page::STATUS_PUBLISHED]);
        $post = $this->createPost(['title' => 'Hello world', 'slug' => 'hello-world', 'status' => Post::STATUS_PUBLISHED]);
        $menu = $this->createMenu();

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/menus/'.$menu->id.'/items', [
                'items' => [
                    ['id' => 'a', 'label' => 'Home', 'type' => 'custom', 'url' => '/'],
                    ['id' => 'b', 'label' => 'About', 'type' => 'page', 'target_id' => $page->id],
                    ['id' => 'c', 'label' => 'News', 'type' => 'post', 'target_id' => $post->id, 'parent_id' => 'b'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $rows = DB::table('menu_items')->where('menu_id', $menu->id)->orderBy('order')->get();

        $this->assertCount(3, $rows);
        $this->assertSame('Home', $rows[0]->label);
        $this->assertSame('About', $rows[1]->label);
        $this->assertSame('News', $rows[2]->label);
        // The child points at the freshly re-created parent row.
        $this->assertSame($rows[1]->id, $rows[2]->parent_id);
        $this->assertSame($page->id, (int) $rows[1]->target_id);
        $this->assertSame($post->id, (int) $rows[2]->target_id);
    }

    public function test_store_items_rejects_a_missing_page_target(): void
    {
        $menu = $this->createMenu();

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/menus/'.$menu->id.'/items', [
                'items' => [
                    ['id' => 'a', 'label' => 'Ghost', 'type' => 'page', 'target_id' => 99999],
                ],
            ])
            ->assertSessionHasErrors('items.0.target_id');
    }

    public function test_store_items_rejects_custom_items_without_url(): void
    {
        $menu = $this->createMenu();

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/menus/'.$menu->id.'/items', [
                'items' => [
                    ['id' => 'a', 'label' => 'External', 'type' => 'custom'],
                ],
            ])
            ->assertSessionHasErrors('items.0.url');
    }

    public function test_store_items_rejects_self_and_two_level_nesting(): void
    {
        $menu = $this->createMenu();

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/menus/'.$menu->id.'/items', [
                'items' => [
                    ['id' => 'a', 'label' => 'Loop', 'type' => 'custom', 'url' => '/loop', 'parent_id' => 'a'],
                ],
            ])
            ->assertSessionHasErrors('items.0.parent_id');

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/menus/'.$menu->id.'/items', [
                'items' => [
                    ['id' => 'a', 'label' => 'Root', 'type' => 'custom', 'url' => '/root'],
                    ['id' => 'b', 'label' => 'Child', 'type' => 'custom', 'url' => '/child', 'parent_id' => 'a'],
                    ['id' => 'c', 'label' => 'Grandchild', 'type' => 'custom', 'url' => '/grandchild', 'parent_id' => 'b'],
                ],
            ])
            ->assertSessionHasErrors('items.2.parent_id');
    }

    public function test_store_items_rejects_empty_labels(): void
    {
        $menu = $this->createMenu();

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/menus/'.$menu->id.'/items', [
                'items' => [
                    ['id' => 'a', 'label' => '', 'type' => 'custom', 'url' => '/path'],
                ],
            ])
            ->assertSessionHasErrors('items.0.label');
    }

    public function test_store_items_rejects_parents_outside_the_request(): void
    {
        $menu = $this->createMenu();

        $this->actingAs($this->adminUser(), 'admin')
            ->post('/admin/menus/'.$menu->id.'/items', [
                'items' => [
                    ['id' => 'a', 'label' => 'Child', 'type' => 'custom', 'url' => '/child', 'parent_id' => '404'],
                ],
            ])
            ->assertSessionHasErrors('items.0.parent_id');
    }

    // ---- frontend rendering --------------------------------------------------

    public function test_primary_menu_replaces_the_pages_nav(): void
    {
        $page = $this->createPage(['title' => 'About us', 'slug' => 'about-us', 'status' => Page::STATUS_PUBLISHED]);
        $post = $this->createPost(['title' => 'Hello world', 'slug' => 'hello-world', 'status' => Post::STATUS_PUBLISHED]);
        $menu = $this->createMenu(['name' => 'Main', 'location' => 'primary']);

        // The published page is deliberately NOT in the menu — the fallback
        // nav would list it, the menu nav must not.
        $this->createItem($menu, ['label' => 'Blog', 'type' => 'post', 'target_id' => $post->id, 'order' => 0]);
        $this->createItem($menu, ['label' => 'Docs', 'type' => 'custom', 'url' => 'https://example.com/docs', 'order' => 1]);

        $this->get('/about-us')
            ->assertOk()
            ->assertSee('href="/blog/hello-world"', false)
            ->assertSee('Blog</a>', false)
            ->assertSee('href="https://example.com/docs" target="_blank" rel="noopener"', false)
            ->assertDontSee('href="/about-us"', false);
    }

    public function test_menu_children_render_nested(): void
    {
        $menu = $this->createMenu(['name' => 'Main', 'location' => 'primary']);

        $parent = $this->createItem($menu, ['label' => 'Home', 'type' => 'custom', 'url' => '/', 'order' => 0]);
        $this->createItem($menu, ['label' => 'Contact', 'type' => 'custom', 'url' => '/contact', 'order' => 1, 'parent_id' => $parent->id]);

        $this->get('/')
            ->assertOk()
            ->assertSee('<ul class="site-nav-children">', false)
            ->assertSeeInOrder(['Home', 'Contact']);
    }

    public function test_menu_items_render_in_saved_order(): void
    {
        $menu = $this->createMenu(['name' => 'Main', 'location' => 'primary']);

        $this->createItem($menu, ['label' => 'Alpha', 'type' => 'custom', 'url' => '/alpha', 'order' => 5]);
        $this->createItem($menu, ['label' => 'Beta', 'type' => 'custom', 'url' => '/beta', 'order' => 9]);
        $this->createItem($menu, ['label' => 'Gamma', 'type' => 'custom', 'url' => '/gamma', 'order' => 2]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Gamma', 'Alpha', 'Beta']);
    }

    public function test_deleting_the_menu_restores_the_pages_fallback(): void
    {
        $page = $this->createPage(['title' => 'About us', 'slug' => 'about-us', 'status' => Page::STATUS_PUBLISHED]);
        $menu = $this->createMenu(['name' => 'Main', 'location' => 'primary']);
        $this->createItem($menu, ['label' => 'Docs', 'type' => 'custom', 'url' => 'https://example.com/docs', 'order' => 0]);

        $this->get('/about-us')->assertOk()->assertDontSee('href="/about-us"', false);

        $menu->delete();

        $this->get('/about-us')
            ->assertOk()
            // The automatic published-pages nav is back, page itself included.
            ->assertSee('href="/about-us"', false);
    }

    public function test_unpublished_targets_are_skipped(): void
    {
        $draft = $this->createPage(['title' => 'Sneak peek', 'slug' => 'sneak-peek']);
        $published = $this->createPage(['title' => 'About us', 'slug' => 'about-us', 'status' => Page::STATUS_PUBLISHED]);
        $menu = $this->createMenu(['name' => 'Main', 'location' => 'primary']);

        $this->createItem($menu, ['label' => 'Sneak peek', 'type' => 'page', 'target_id' => $draft->id, 'order' => 0]);
        $this->createItem($menu, ['label' => 'About', 'type' => 'page', 'target_id' => $published->id, 'order' => 1]);

        $this->get('/')
            ->assertOk()
            ->assertSee('href="/about-us"', false)
            ->assertDontSee('href="/sneak-peek"', false);
    }

    // ---- helpers ------------------------------------------------------------

    private function createMenu(array $attributes = []): Menu
    {
        return Menu::query()->create([
            'name' => 'Main menu',
            'slug' => 'main-menu-'.uniqid(),
            ...$attributes,
        ]);
    }

    private function createItem(Menu $menu, array $attributes = []): MenuItem
    {
        return MenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Item',
            'type' => 'custom',
            'url' => '/item',
            'order' => 0,
            ...$attributes,
        ]);
    }

    private function createPage(array $attributes = []): Page
    {
        return Page::query()->create([
            'title' => 'Untitled',
            'slug' => 'untitled-'.uniqid(),
            'content' => '<p>Page body copy</p>',
            ...$attributes,
        ]);
    }

    private function createPost(array $attributes = []): Post
    {
        return Post::query()->create([
            'title' => 'Untitled',
            'slug' => 'untitled-'.uniqid(),
            'content' => '<p>Post body copy</p>',
            ...$attributes,
        ]);
    }

    private function userWithMenusManage(): User
    {
        $user = $this->plainAdmin();
        $role = Role::factory()->create();
        $permission = Permission::query()->firstOrCreate(
            ['key' => 'menus.manage'],
            [
                'name' => 'menus.manage',
                'module' => 'core/base',
                'group' => 'menus',
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
}
