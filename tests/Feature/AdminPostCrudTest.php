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
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

class AdminPostCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_posts(): void
    {
        $this->get('/admin/posts')
            ->assertRedirect('/admin/login');
    }

    public function test_post_routes_require_their_permissions(): void
    {
        $user = $this->plainAdmin();
        $post = $this->createPost(['title' => 'First post', 'slug' => 'first-post']);

        $this->actingAs($user, 'admin')
            ->get('/admin/posts')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get('/admin/posts/create')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->post('/admin/posts', [
                'title' => 'Blocked post',
                'status' => Post::STATUS_DRAFT,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get('/admin/posts/'.$post->id.'/edit')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->put('/admin/posts/'.$post->id, [
                'title' => 'Blocked update',
                'status' => Post::STATUS_DRAFT,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->delete('/admin/posts/'.$post->id)
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get('/admin/posts/'.$post->id.'/preview')
            ->assertForbidden();
    }

    public function test_post_permissions_are_registered(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);

        foreach (['post.index', 'post.create', 'post.edit', 'post.delete'] as $key) {
            $this->assertTrue($registry->has($key));
        }

        $permission = $registry->all()->firstWhere('key', 'post.index');

        $this->assertSame('post', $permission['group']);
        $this->assertSame('package/blog', $permission['module']);
    }

    public function test_posts_sidebar_item_requires_post_index_permission(): void
    {
        $registry = $this->app->make(AdminMenuRegistry::class);

        $this->assertTrue($registry->has('posts'));

        $viewer = $this->userWithPermissions(['post.index']);

        $this->assertTrue($registry->visibleFor($viewer)->pluck('id')->contains('posts'));
        $this->assertFalse($registry->visibleFor($this->plainAdmin())->pluck('id')->contains('posts'));
    }

    public function test_super_admin_can_view_posts_index_with_sidebar_menu(): void
    {
        $admin = $this->adminUser();
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);
        $draft = $this->createPost(['title' => 'First post', 'slug' => 'first-post']);
        $published = $this->createPost([
            'title' => 'Second post',
            'slug' => 'second-post',
            'status' => Post::STATUS_PUBLISHED,
            'category_id' => $category->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/posts')
            ->assertOk()
            ->assertSee('navbar-vertical', false)
            ->assertSee('Posts')
            ->assertSee('Post list')
            ->assertSee('First post')
            ->assertSee('Second post')
            ->assertSee('News')
            ->assertSee('Draft')
            ->assertSee('Published')
            ->assertSee(route('admin.posts.edit', $draft), false)
            ->assertSee(route('admin.posts.edit', $published), false)
            ->assertSee('data-bs-target="#post-delete-'.$draft->id.'"', false);
    }

    public function test_super_admin_can_store_post_with_generated_slug_and_draft_status(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->post('/admin/posts', [
                'title' => 'Hello World',
                'slug' => '',
                'content' => '<p>First body copy</p>',
                'status' => Post::STATUS_DRAFT,
            ])
            ->assertRedirect('/admin/posts')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('posts', [
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'status' => Post::STATUS_DRAFT,
            'content' => '<p>First body copy</p>',
        ]);
    }

    public function test_store_post_suffixes_slug_when_a_page_owns_the_title(): void
    {
        $admin = $this->adminUser();
        Page::query()->create([
            'title' => 'About us',
            'slug' => 'about-us',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/posts', [
                'title' => 'About us',
                'status' => Post::STATUS_DRAFT,
            ])
            ->assertRedirect('/admin/posts');

        $this->assertDatabaseHas('posts', [
            'title' => 'About us',
            'slug' => 'about-us-2',
        ]);
    }

    public function test_store_post_suffixes_manual_slug_taken_by_another_post(): void
    {
        $admin = $this->adminUser();
        $this->createPost(['title' => 'First post', 'slug' => 'first-post']);

        $this->actingAs($admin, 'admin')
            ->post('/admin/posts', [
                'title' => 'Second post',
                'slug' => 'first-post',
                'status' => Post::STATUS_DRAFT,
            ])
            ->assertRedirect('/admin/posts');

        $this->assertDatabaseHas('posts', [
            'title' => 'Second post',
            'slug' => 'first-post-2',
        ]);
        $this->assertDatabaseHas('posts', [
            'title' => 'First post',
            'slug' => 'first-post',
        ]);
    }

    public function test_update_post_suffixes_manual_slug_taken_by_a_page(): void
    {
        $admin = $this->adminUser();
        Page::query()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'status' => Page::STATUS_PUBLISHED,
        ]);
        $post = $this->createPost(['title' => 'First post', 'slug' => 'first-post']);

        $this->actingAs($admin, 'admin')
            ->put('/admin/posts/'.$post->id, [
                'title' => 'First post',
                'slug' => 'contact',
                'status' => Post::STATUS_DRAFT,
            ])
            ->assertRedirect('/admin/posts/'.$post->id.'/edit')
            ->assertSessionHas('status');

        $post->refresh();

        $this->assertSame('contact-2', $post->slug);
        $this->assertDatabaseHas('pages', [
            'title' => 'Contact',
            'slug' => 'contact',
        ]);
    }

    public function test_super_admin_can_assign_category_to_post(): void
    {
        $admin = $this->adminUser();
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);

        $this->actingAs($admin, 'admin')
            ->post('/admin/posts', [
                'title' => 'Categorized post',
                'status' => Post::STATUS_DRAFT,
                'category_id' => $category->id,
            ])
            ->assertRedirect('/admin/posts')
            ->assertSessionHas('status');

        $post = Post::query()->where('slug', 'categorized-post')->first();

        $this->assertNotNull($post);
        $this->assertSame($category->id, $post->category_id);
    }

    public function test_super_admin_can_update_post_category_and_featured_image(): void
    {
        $admin = $this->adminUser();
        $news = Category::query()->create(['name' => 'News', 'slug' => 'news']);
        $technology = Category::query()->create(['name' => 'Technology', 'slug' => 'technology']);
        $post = $this->createPost([
            'title' => 'Categorized post',
            'slug' => 'categorized-post',
            'category_id' => $news->id,
            'featured_image' => '/storage/old-hero.jpg',
        ]);

        $this->actingAs($admin, 'admin')
            ->put('/admin/posts/'.$post->id, [
                'title' => 'Categorized post',
                'status' => Post::STATUS_DRAFT,
                'category_id' => $technology->id,
                'featured_image' => '/storage/new-hero.jpg',
            ])
            ->assertRedirect('/admin/posts/'.$post->id.'/edit')
            ->assertSessionHas('status');

        $post->refresh();

        $this->assertSame($technology->id, $post->category_id);
        $this->assertSame('/storage/new-hero.jpg', $post->featured_image);
    }

    public function test_store_post_syncs_tags_from_comma_separated_input(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->post('/admin/posts', [
                'title' => 'Tagged post',
                'status' => Post::STATUS_DRAFT,
                'tags_input' => 'Laravel, PHP',
            ])
            ->assertRedirect('/admin/posts')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('tags', ['name' => 'Laravel', 'slug' => 'laravel']);
        $this->assertDatabaseHas('tags', ['name' => 'PHP', 'slug' => 'php']);

        $post = Post::query()->where('slug', 'tagged-post')->with('tags')->first();

        $this->assertNotNull($post);
        $this->assertSame(['Laravel', 'PHP'], $post->tags->pluck('name')->all());
    }

    public function test_update_post_resyncs_tags_from_scratch(): void
    {
        $admin = $this->adminUser();
        $post = $this->createPost(['title' => 'Tagged post', 'slug' => 'tagged-post']);

        $this->actingAs($admin, 'admin')
            ->put('/admin/posts/'.$post->id, [
                'title' => 'Tagged post',
                'status' => Post::STATUS_DRAFT,
                'tags_input' => 'Laravel, PHP',
            ])
            ->assertRedirect('/admin/posts/'.$post->id.'/edit')
            ->assertSessionHas('status');

        $this->actingAs($admin, 'admin')
            ->put('/admin/posts/'.$post->id, [
                'title' => 'Tagged post',
                'status' => Post::STATUS_DRAFT,
                'tags_input' => 'Laravel',
            ])
            ->assertRedirect('/admin/posts/'.$post->id.'/edit')
            ->assertSessionHas('status');

        $post->refresh();

        $this->assertSame(['Laravel'], $post->tags->pluck('name')->all());
        // Resyncing only detaches the pivot; previously created tags remain
        // in the library so other posts keep using them.
        $this->assertDatabaseHas('tags', ['name' => 'PHP']);
        $this->assertDatabaseCount('tags', 2);
    }

    public function test_store_post_does_not_duplicate_existing_tag_names(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->post('/admin/posts', [
                'title' => 'Duplicated tags',
                'status' => Post::STATUS_DRAFT,
                'tags_input' => 'PHP, PHP, php',
            ])
            ->assertRedirect('/admin/posts');

        $this->assertDatabaseCount('tags', 1);

        $post = Post::query()->where('slug', 'duplicated-tags')->with('tags')->first();

        $this->assertNotNull($post);
        $this->assertSame(['PHP'], $post->tags->pluck('name')->all());
    }

    public function test_super_admin_can_store_post_with_featured_image_url(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->post('/admin/posts', [
                'title' => 'Featured post',
                'status' => Post::STATUS_DRAFT,
                'featured_image' => '/storage/hero.jpg',
            ])
            ->assertRedirect('/admin/posts')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('posts', [
            'title' => 'Featured post',
            'featured_image' => '/storage/hero.jpg',
        ]);

        $post = Post::query()->where('slug', 'featured-post')->first();

        $this->actingAs($admin, 'admin')
            ->get('/admin/posts/'.$post->id.'/edit')
            ->assertOk()
            ->assertSee('name="featured_image"', false)
            ->assertSee('value="/storage/hero.jpg"', false);
    }

    public function test_super_admin_can_store_post_with_seo_and_og_image_fields(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->post('/admin/posts', [
                'title' => 'SEO post',
                'status' => Post::STATUS_DRAFT,
                'seo_title' => 'Custom SEO title',
                'seo_description' => 'Custom SEO description for search engines.',
                'og_image' => '/storage/social-card.jpg',
            ])
            ->assertRedirect('/admin/posts')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('posts', [
            'slug' => 'seo-post',
            'seo_title' => 'Custom SEO title',
            'seo_description' => 'Custom SEO description for search engines.',
            'og_image' => '/storage/social-card.jpg',
        ]);
    }

    public function test_super_admin_can_update_post_seo_and_og_image_fields(): void
    {
        $admin = $this->adminUser();
        $post = $this->createPost(['title' => 'First post', 'slug' => 'first-post']);

        $this->actingAs($admin, 'admin')
            ->put('/admin/posts/'.$post->id, [
                'title' => 'First post',
                'status' => Post::STATUS_PUBLISHED,
                'seo_title' => 'Renamed SEO title',
                'seo_description' => 'Updated SEO description.',
                'og_image' => '/storage/post-card.jpg',
            ])
            ->assertRedirect('/admin/posts/'.$post->id.'/edit')
            ->assertSessionHas('status');

        $post->refresh();

        $this->assertSame('Renamed SEO title', $post->seo_title);
        $this->assertSame('Updated SEO description.', $post->seo_description);
        $this->assertSame('/storage/post-card.jpg', $post->og_image);
    }

    public function test_post_edit_form_renders_seo_section_with_use_featured_copy(): void
    {
        $admin = $this->adminUser();
        $post = $this->createPost([
            'title' => 'First post',
            'slug' => 'first-post',
            'featured_image' => '/storage/hero.jpg',
            'seo_title' => 'SEO title copy',
            'seo_description' => 'SEO description copy.',
            'og_image' => '/storage/post-card.jpg',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/posts/'.$post->id.'/edit')
            ->assertOk()
            ->assertSee('data-seo-counter="60"', false)
            ->assertSee('data-seo-counter="160"', false)
            ->assertSee('name="og_image"', false)
            ->assertSee('value="/storage/post-card.jpg"', false)
            ->assertSee('data-seo-og-copy', false);
    }

    public function test_store_post_rejects_og_image_longer_than_255(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->post('/admin/posts', [
                'title' => 'SEO post',
                'status' => Post::STATUS_DRAFT,
                'seo_title' => 'Old SEO title',
                'og_image' => str_repeat('a', 256),
            ])
            ->assertSessionHasErrors('og_image');
    }

    public function test_super_admin_can_update_post_title_keeping_slug(): void
    {
        $admin = $this->adminUser();
        $post = $this->createPost([
            'title' => 'First post',
            'slug' => 'first-post',
            'content' => '<p>Old body</p>',
        ]);

        $this->actingAs($admin, 'admin')
            ->put('/admin/posts/'.$post->id, [
                'title' => 'Renamed post',
                'slug' => '',
                'content' => '<p>New body</p>',
                'status' => Post::STATUS_PUBLISHED,
            ])
            ->assertRedirect('/admin/posts/'.$post->id.'/edit')
            ->assertSessionHas('status');

        $post->refresh();

        $this->assertSame('Renamed post', $post->title);
        $this->assertSame('first-post', $post->slug);
        $this->assertSame(Post::STATUS_PUBLISHED, $post->status);
        $this->assertSame('<p>New body</p>', $post->content);
    }

    public function test_super_admin_can_edit_post_with_editor_and_preview_link(): void
    {
        $admin = $this->adminUser();
        $post = $this->createPost([
            'title' => 'First post',
            'slug' => 'first-post',
            'content' => '<p>Body copy</p>',
            'status' => Post::STATUS_DRAFT,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/posts/'.$post->id.'/edit')
            ->assertOk()
            ->assertSee('First post')
            ->assertSee('first-post')
            ->assertSee('data-admin-editor', false)
            ->assertSee('value="first-post"', false)
            ->assertSee('Leave blank to keep the current slug.')
            ->assertSee(route('admin.posts.preview', $post), false)
            ->assertSee('data-media-picker', false)
            ->assertSee('data-post-featured', false);
    }

    public function test_super_admin_can_delete_post(): void
    {
        $admin = $this->adminUser();
        $post = $this->createPost(['title' => 'First post', 'slug' => 'first-post']);

        $this->actingAs($admin, 'admin')
            ->delete('/admin/posts/'.$post->id)
            ->assertRedirect('/admin/posts')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_posts_index_supports_search_status_and_category_filters(): void
    {
        $admin = $this->adminUser();
        $news = Category::query()->create(['name' => 'News', 'slug' => 'news']);
        $this->createPost(['title' => 'First post', 'slug' => 'first-post']);
        $this->createPost([
            'title' => 'Second post',
            'slug' => 'second-post',
            'status' => Post::STATUS_PUBLISHED,
            'category_id' => $news->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/posts?q=First')
            ->assertOk()
            ->assertSee('First post')
            ->assertDontSee('Second post');

        $this->actingAs($admin, 'admin')
            ->get('/admin/posts?status='.Post::STATUS_PUBLISHED)
            ->assertOk()
            ->assertSee('Second post')
            ->assertDontSee('First post');

        $this->actingAs($admin, 'admin')
            ->get('/admin/posts?category_id='.$news->id)
            ->assertOk()
            ->assertSee('Second post')
            ->assertDontSee('First post');
    }

    public function test_preview_shows_draft_posts_with_watermark(): void
    {
        $admin = $this->adminUser();
        $draft = $this->createPost([
            'title' => 'Coming soon',
            'slug' => 'coming-soon',
            'content' => '<p>Draft body copy</p>',
            'status' => Post::STATUS_DRAFT,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/posts/'.$draft->id.'/preview')
            ->assertOk()
            ->assertSee('PREVIEW — DRAFT')
            ->assertSee('Coming soon')
            ->assertSee('<p>Draft body copy</p>', false);
    }

    public function test_preview_shows_published_posts_without_draft_watermark(): void
    {
        $admin = $this->adminUser();
        $published = $this->createPost([
            'title' => 'Launch day',
            'slug' => 'launch-day',
            'content' => '<p>Published body copy</p>',
            'status' => Post::STATUS_PUBLISHED,
            'featured_image' => '/storage/hero.jpg',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/posts/'.$published->id.'/preview')
            ->assertOk()
            ->assertSee('Launch day')
            ->assertSee('<p>Published body copy</p>', false)
            ->assertSee('<img src="/storage/hero.jpg"', false)
            ->assertDontSee('PREVIEW — DRAFT');
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
                    'group' => 'post',
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
