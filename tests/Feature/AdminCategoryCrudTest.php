<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Sitewyn\Core\Base\Support\PermissionRegistry;
use Sitewyn\Packages\Blog\Models\Category;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Blog\Repositories\CategoryRepository;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

class AdminCategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_categories(): void
    {
        $this->get('/admin/categories')
            ->assertRedirect('/admin/login');
    }

    public function test_category_routes_require_their_permissions(): void
    {
        $user = $this->plainAdmin();
        $category = $this->createCategory(['name' => 'News', 'slug' => 'news']);

        $this->actingAs($user, 'admin')
            ->get('/admin/categories')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get('/admin/categories/create')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->post('/admin/categories', [
                'name' => 'Blocked category',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get('/admin/categories/'.$category->id.'/edit')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->put('/admin/categories/'.$category->id, [
                'name' => 'Blocked update',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->delete('/admin/categories/'.$category->id)
            ->assertForbidden();
    }

    public function test_category_permissions_are_registered(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);

        foreach (['category.index', 'category.create', 'category.edit', 'category.delete'] as $key) {
            $this->assertTrue($registry->has($key));
        }

        $permission = $registry->all()->firstWhere('key', 'category.index');

        $this->assertSame('category', $permission['group']);
        $this->assertSame('package/blog', $permission['module']);
    }

    public function test_categories_sidebar_item_requires_category_index_permission(): void
    {
        $registry = $this->app->make(AdminMenuRegistry::class);

        $this->assertTrue($registry->has('categories'));

        $viewer = $this->userWithPermissions(['category.index']);

        $this->assertTrue($registry->visibleFor($viewer)->pluck('id')->contains('categories'));
        $this->assertFalse($registry->visibleFor($this->plainAdmin())->pluck('id')->contains('categories'));
    }

    public function test_super_admin_can_view_categories_index_with_sidebar_menu(): void
    {
        $admin = $this->adminUser();
        $news = $this->createCategory(['name' => 'News', 'slug' => 'news']);
        $tech = $this->createCategory([
            'name' => 'Technology',
            'slug' => 'technology',
            'parent_id' => $news->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/categories')
            ->assertOk()
            ->assertSee('navbar-vertical', false)
            ->assertSee('Categories')
            ->assertSee('Tags')
            ->assertSee('Category list')
            ->assertSee('News')
            ->assertSee('Technology')
            ->assertSee(route('admin.categories.edit', $tech), false)
            ->assertSee(route('admin.categories.edit', $news), false)
            ->assertSee('data-bs-target="#category-delete-'.$news->id.'"', false);
    }

    public function test_super_admin_can_store_category_with_generated_slug(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->post('/admin/categories', [
                'name' => 'Hello World',
                'slug' => '',
            ])
            ->assertRedirect('/admin/categories')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('categories', [
            'name' => 'Hello World',
            'slug' => 'hello-world',
            'parent_id' => null,
        ]);
    }

    public function test_super_admin_can_store_category_with_manual_slug_and_parent(): void
    {
        $admin = $this->adminUser();
        $parent = $this->createCategory(['name' => 'News', 'slug' => 'news']);

        $this->actingAs($admin, 'admin')
            ->post('/admin/categories', [
                'name' => 'World News',
                'slug' => 'world-news',
                'parent_id' => $parent->id,
            ])
            ->assertRedirect('/admin/categories')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('categories', [
            'name' => 'World News',
            'slug' => 'world-news',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_store_category_suffixes_duplicate_slug_within_its_own_namespace(): void
    {
        $admin = $this->adminUser();
        $this->createCategory(['name' => 'News', 'slug' => 'news']);

        $this->actingAs($admin, 'admin')
            ->post('/admin/categories', [
                'name' => 'News',
            ])
            ->assertRedirect('/admin/categories');

        $this->assertDatabaseHas('categories', [
            'name' => 'News',
            'slug' => 'news-2',
        ]);
        $this->assertDatabaseCount('categories', 2);
    }

    public function test_category_slugs_are_independent_of_the_pages_namespace(): void
    {
        $admin = $this->adminUser();
        Page::query()->create([
            'title' => 'News',
            'slug' => 'news',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/categories', [
                'name' => 'News',
            ])
            ->assertRedirect('/admin/categories');

        // The pages slug must not force a suffix on the category slug.
        $this->assertDatabaseHas('categories', [
            'name' => 'News',
            'slug' => 'news',
        ]);
    }

    public function test_super_admin_can_update_category_title_keeping_slug(): void
    {
        $admin = $this->adminUser();
        $category = $this->createCategory([
            'name' => 'News',
            'slug' => 'news',
            'description' => 'Old description.',
        ]);

        $this->actingAs($admin, 'admin')
            ->put('/admin/categories/'.$category->id, [
                'name' => 'Renamed category',
                'slug' => '',
                'description' => 'New description.',
            ])
            ->assertRedirect('/admin/categories/'.$category->id.'/edit')
            ->assertSessionHas('status');

        $category->refresh();

        $this->assertSame('Renamed category', $category->name);
        $this->assertSame('news', $category->slug);
        $this->assertSame('New description.', $category->description);
    }

    public function test_update_category_suffixes_manual_slug_when_taken(): void
    {
        $admin = $this->adminUser();
        $other = $this->createCategory(['name' => 'News', 'slug' => 'news']);
        $category = $this->createCategory(['name' => 'Lifestyle', 'slug' => 'lifestyle']);

        $this->actingAs($admin, 'admin')
            ->put('/admin/categories/'.$category->id, [
                'name' => 'Lifestyle',
                'slug' => 'news',
            ])
            ->assertRedirect('/admin/categories/'.$category->id.'/edit')
            ->assertSessionHas('status');

        $category->refresh();

        $this->assertSame('news-2', $category->slug);
        $this->assertDatabaseHas('categories', [
            'id' => $other->id,
            'slug' => 'news',
        ]);
    }

    public function test_update_category_cannot_select_itself_as_parent(): void
    {
        $admin = $this->adminUser();
        $category = $this->createCategory(['name' => 'News', 'slug' => 'news']);

        $this->actingAs($admin, 'admin')
            ->put('/admin/categories/'.$category->id, [
                'name' => 'News',
                'parent_id' => $category->id,
            ])
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'parent_id' => null,
        ]);
    }

    public function test_update_category_cannot_select_a_descendant_as_parent(): void
    {
        $admin = $this->adminUser();
        $parent = $this->createCategory(['name' => 'News', 'slug' => 'news']);
        $child = $this->createCategory([
            'name' => 'Technology',
            'slug' => 'technology',
            'parent_id' => $parent->id,
        ]);
        $grandchild = $this->createCategory([
            'name' => 'Gadgets',
            'slug' => 'gadgets',
            'parent_id' => $child->id,
        ]);

        foreach ([$child->id, $grandchild->id] as $attemptedParentId) {
            $this->actingAs($admin, 'admin')
                ->put('/admin/categories/'.$parent->id, [
                    'name' => 'News',
                    'parent_id' => $attemptedParentId,
                ])
                ->assertSessionHasErrors('parent_id');
        }

        $this->assertDatabaseHas('categories', [
            'id' => $parent->id,
            'parent_id' => null,
        ]);
    }

    public function test_update_category_rejects_unknown_parent(): void
    {
        $admin = $this->adminUser();
        $category = $this->createCategory(['name' => 'News', 'slug' => 'news']);

        $this->actingAs($admin, 'admin')
            ->put('/admin/categories/'.$category->id, [
                'name' => 'News',
                'parent_id' => 99999,
            ])
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'parent_id' => null,
        ]);
    }

    public function test_edit_form_excludes_the_category_itself_and_its_descendants_from_parent_choices(): void
    {
        $admin = $this->adminUser();
        $parent = $this->createCategory(['name' => 'News', 'slug' => 'news']);
        $child = $this->createCategory([
            'name' => 'Technology',
            'slug' => 'technology',
            'parent_id' => $parent->id,
        ]);
        $grandchild = $this->createCategory([
            'name' => 'Gadgets',
            'slug' => 'gadgets',
            'parent_id' => $child->id,
        ]);
        $unrelated = $this->createCategory(['name' => 'Lifestyle', 'slug' => 'lifestyle']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/categories/'.$parent->id.'/edit')
            ->assertOk()
            ->assertDontSee('<option value="'.$parent->id.'"', false)
            ->assertDontSee('<option value="'.$child->id.'"', false)
            ->assertDontSee('<option value="'.$grandchild->id.'"', false)
            ->assertSee('<option value="'.$unrelated->id.'"', false)
            ->assertSee('— None —');

        // The create form offers every existing category as a parent.
        $this->actingAs($admin, 'admin')
            ->get('/admin/categories/create')
            ->assertOk()
            ->assertSee('<option value="'.$parent->id.'"', false)
            ->assertSee('<option value="'.$child->id.'"', false);
    }

    public function test_deleting_parent_category_moves_children_to_root(): void
    {
        $admin = $this->adminUser();
        $parent = $this->createCategory(['name' => 'News', 'slug' => 'news']);
        $child = $this->createCategory([
            'name' => 'Technology',
            'slug' => 'technology',
            'parent_id' => $parent->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->delete('/admin/categories/'.$parent->id)
            ->assertRedirect('/admin/categories')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('categories', ['id' => $parent->id]);
        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => null,
        ]);
        $this->assertSame(
            [$child->id],
            (new CategoryRepository)->childrenOf(null)->pluck('id')->all(),
        );
    }

    public function test_deleting_category_detaches_its_posts(): void
    {
        $admin = $this->adminUser();
        $category = $this->createCategory(['name' => 'News', 'slug' => 'news']);
        $post = $this->createPost(['category_id' => $category->id]);

        $this->actingAs($admin, 'admin')
            ->delete('/admin/categories/'.$category->id)
            ->assertRedirect('/admin/categories');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'category_id' => null,
        ]);
        $this->assertNull($post->refresh()->category_id);
    }

    public function test_categories_index_supports_name_search(): void
    {
        $admin = $this->adminUser();
        $this->createCategory(['name' => 'News', 'slug' => 'news']);
        $this->createCategory(['name' => 'Lifestyle', 'slug' => 'lifestyle']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/categories?q=News')
            ->assertOk()
            ->assertSee('News')
            ->assertDontSee('Lifestyle');
    }

    private function createCategory(array $attributes = []): Category
    {
        return Category::query()->create([
            'name' => 'Untitled',
            'slug' => 'untitled-'.uniqid(),
            ...$attributes,
        ]);
    }

    private function createPost(array $attributes = []): Post
    {
        return Post::query()->create([
            'title' => 'Untitled',
            'slug' => 'untitled-'.uniqid(),
            'status' => Post::STATUS_DRAFT,
            ...$attributes,
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
            $permission = Permission::query()->firstOrCreate(
                ['key' => $key],
                [
                    'name' => $key,
                    'module' => 'package/blog',
                    'group' => 'category',
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
}
