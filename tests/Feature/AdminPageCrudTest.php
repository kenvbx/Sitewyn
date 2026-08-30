<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Sitewyn\Core\Base\Support\PermissionRegistry;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

class AdminPageCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_pages(): void
    {
        $this->get('/admin/pages')
            ->assertRedirect('/admin/login');
    }

    public function test_page_routes_require_their_permissions(): void
    {
        $user = $this->plainAdmin();
        $page = $this->createPage(['title' => 'About us', 'slug' => 'about-us']);

        $this->actingAs($user, 'admin')
            ->get('/admin/pages')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get('/admin/pages/create')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->post('/admin/pages', [
                'title' => 'Blocked page',
                'status' => Page::STATUS_DRAFT,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get('/admin/pages/'.$page->id.'/edit')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->put('/admin/pages/'.$page->id, [
                'title' => 'Blocked update',
                'status' => Page::STATUS_DRAFT,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->delete('/admin/pages/'.$page->id)
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get('/admin/pages/'.$page->id.'/preview')
            ->assertForbidden();
    }

    public function test_page_permissions_are_registered(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);

        foreach (['page.index', 'page.create', 'page.edit', 'page.delete'] as $key) {
            $this->assertTrue($registry->has($key));
        }

        $permission = $registry->all()->firstWhere('key', 'page.index');

        $this->assertSame('page', $permission['group']);
        $this->assertSame('package/page', $permission['module']);
    }

    public function test_pages_sidebar_item_requires_page_index_permission(): void
    {
        $registry = $this->app->make(AdminMenuRegistry::class);

        $this->assertTrue($registry->has('pages'));

        $viewer = $this->userWithPermissions(['page.index']);

        $this->assertTrue($registry->visibleFor($viewer)->pluck('id')->contains('pages'));
        $this->assertFalse($registry->visibleFor($this->plainAdmin())->pluck('id')->contains('pages'));
    }

    public function test_super_admin_can_view_pages_index_with_sidebar_menu(): void
    {
        $admin = $this->adminUser();
        $draft = $this->createPage(['title' => 'About us', 'slug' => 'about-us']);
        $published = $this->createPage([
            'title' => 'Contact',
            'slug' => 'contact',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/pages')
            ->assertOk()
            ->assertSee('navbar-vertical', false)
            ->assertSee('Pages')
            ->assertSee('Page list')
            ->assertSee('About us')
            ->assertSee('about-us')
            ->assertSee('Contact')
            ->assertSee('Draft')
            ->assertSee('Published')
            ->assertSee(route('admin.pages.edit', $draft), false)
            ->assertSee(route('admin.pages.edit', $published), false)
            ->assertSee('data-bs-target="#page-delete-'.$draft->id.'"', false);
    }

    public function test_super_admin_can_store_page_with_generated_slug_and_draft_status(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->post('/admin/pages', [
                'title' => 'Hello World',
                'slug' => '',
                'content' => '<p>First body copy</p>',
                'status' => Page::STATUS_DRAFT,
            ])
            ->assertRedirect('/admin/pages')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('pages', [
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'status' => Page::STATUS_DRAFT,
            'content' => '<p>First body copy</p>',
        ]);
    }

    public function test_store_page_suffixes_slug_when_title_is_taken(): void
    {
        $admin = $this->adminUser();
        $this->createPage(['title' => 'About us', 'slug' => 'about-us']);

        $this->actingAs($admin, 'admin')
            ->post('/admin/pages', [
                'title' => 'About us',
                'status' => Page::STATUS_DRAFT,
            ])
            ->assertRedirect('/admin/pages');

        $this->assertDatabaseHas('pages', [
            'title' => 'About us',
            'slug' => 'about-us-2',
        ]);
    }

    public function test_store_page_suffixes_manual_slug_taken_by_a_post(): void
    {
        $admin = $this->adminUser();
        Post::query()->create([
            'title' => 'Launch day',
            'slug' => 'launch-day',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/pages', [
                'title' => 'Launch day',
                'slug' => 'launch-day',
                'status' => Page::STATUS_PUBLISHED,
            ])
            ->assertRedirect('/admin/pages');

        $this->assertDatabaseHas('pages', [
            'title' => 'Launch day',
            'slug' => 'launch-day-2',
        ]);
        $this->assertDatabaseHas('posts', [
            'title' => 'Launch day',
            'slug' => 'launch-day',
        ]);
    }

    public function test_super_admin_can_update_page_title_keeping_slug(): void
    {
        $admin = $this->adminUser();
        $page = $this->createPage([
            'title' => 'About us',
            'slug' => 'about-us',
            'content' => '<p>Old body</p>',
        ]);

        $this->actingAs($admin, 'admin')
            ->put('/admin/pages/'.$page->id, [
                'title' => 'About Sitewyn',
                'slug' => '',
                'content' => '<p>New body</p>',
                'status' => Page::STATUS_PUBLISHED,
            ])
            ->assertRedirect('/admin/pages/'.$page->id.'/edit')
            ->assertSessionHas('status');

        $page->refresh();

        $this->assertSame('About Sitewyn', $page->title);
        $this->assertSame('about-us', $page->slug);
        $this->assertSame(Page::STATUS_PUBLISHED, $page->status);
        $this->assertSame('<p>New body</p>', $page->content);
    }

    public function test_update_page_suffixes_manual_slug_taken_by_a_post(): void
    {
        $admin = $this->adminUser();
        Post::query()->create([
            'title' => 'Launch day',
            'slug' => 'launch-day',
            'status' => Post::STATUS_PUBLISHED,
        ]);
        $page = $this->createPage(['title' => 'Contact', 'slug' => 'contact']);

        $this->actingAs($admin, 'admin')
            ->put('/admin/pages/'.$page->id, [
                'title' => 'Contact',
                'slug' => 'launch-day',
                'status' => Page::STATUS_PUBLISHED,
            ])
            ->assertRedirect('/admin/pages/'.$page->id.'/edit')
            ->assertSessionHas('status');

        $page->refresh();

        $this->assertSame('launch-day-2', $page->slug);
        $this->assertDatabaseHas('posts', [
            'title' => 'Launch day',
            'slug' => 'launch-day',
        ]);
    }

    public function test_super_admin_can_store_page_with_seo_fields(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->post('/admin/pages', [
                'title' => 'SEO page',
                'status' => Page::STATUS_DRAFT,
                'seo_title' => 'Custom SEO title',
                'seo_description' => 'Custom SEO description for search engines.',
                'og_image' => '/storage/social-card.jpg',
            ])
            ->assertRedirect('/admin/pages')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('pages', [
            'slug' => 'seo-page',
            'seo_title' => 'Custom SEO title',
            'seo_description' => 'Custom SEO description for search engines.',
            'og_image' => '/storage/social-card.jpg',
        ]);
    }

    public function test_super_admin_can_update_page_seo_fields(): void
    {
        $admin = $this->adminUser();
        $page = $this->createPage(['title' => 'About us', 'slug' => 'about-us']);

        $this->actingAs($admin, 'admin')
            ->put('/admin/pages/'.$page->id, [
                'title' => 'About us',
                'status' => Page::STATUS_PUBLISHED,
                'seo_title' => 'About Sitewyn',
                'seo_description' => 'Updated SEO description.',
                'og_image' => '/storage/about-card.jpg',
            ])
            ->assertRedirect('/admin/pages/'.$page->id.'/edit')
            ->assertSessionHas('status');

        $page->refresh();

        $this->assertSame('About Sitewyn', $page->seo_title);
        $this->assertSame('Updated SEO description.', $page->seo_description);
        $this->assertSame('/storage/about-card.jpg', $page->og_image);
    }

    public function test_page_edit_form_renders_seo_section_with_counters(): void
    {
        $admin = $this->adminUser();
        $page = $this->createPage([
            'title' => 'About us',
            'slug' => 'about-us',
            'seo_title' => 'About Sitewyn',
            'seo_description' => 'About page description.',
            'og_image' => '/storage/about-card.jpg',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/pages/'.$page->id.'/edit')
            ->assertOk()
            ->assertSee('data-seo-counter="60"', false)
            ->assertSee('data-seo-counter="160"', false)
            ->assertSee('name="og_image"', false)
            ->assertSee('value="About Sitewyn"', false)
            ->assertSee('value="/storage/about-card.jpg"', false)
            // The copy-from-featured button is post-form only.
            ->assertDontSee('Use featured image');
    }

    public function test_store_page_rejects_og_image_longer_than_255(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->post('/admin/pages', [
                'title' => 'SEO page',
                'status' => Page::STATUS_DRAFT,
                'seo_title' => 'Old SEO title',
                'og_image' => str_repeat('a', 256),
            ])
            ->assertSessionHasErrors('og_image');

        // The failed store re-renders the form with old input, so the
        // entered SEO fields survive for the user.
        $this->actingAs($admin, 'admin')
            ->get('/admin/pages/create')
            ->assertOk()
            ->assertSee('data-seo-counter="60"', false)
            ->assertSee('value="Old SEO title"', false);
    }

    public function test_super_admin_can_edit_page_with_editor_and_preview_link(): void
    {
        $admin = $this->adminUser();
        $page = $this->createPage([
            'title' => 'About us',
            'slug' => 'about-us',
            'content' => '<p>Body copy</p>',
            'status' => Page::STATUS_DRAFT,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/pages/'.$page->id.'/edit')
            ->assertOk()
            ->assertSee('About us')
            ->assertSee('about-us')
            ->assertSee('data-admin-editor', false)
            ->assertSee('value="about-us"', false)
            ->assertSee('Leave blank to keep the current slug.')
            ->assertSee(route('admin.pages.preview', $page), false)
            ->assertSee('data-media-picker', false);
    }

    public function test_super_admin_can_delete_page(): void
    {
        $admin = $this->adminUser();
        $page = $this->createPage(['title' => 'About us', 'slug' => 'about-us']);

        $this->actingAs($admin, 'admin')
            ->delete('/admin/pages/'.$page->id)
            ->assertRedirect('/admin/pages')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('pages', [
            'id' => $page->id,
        ]);
    }

    public function test_pages_index_supports_search_and_status_filter(): void
    {
        $admin = $this->adminUser();
        $this->createPage(['title' => 'About us', 'slug' => 'about-us']);
        $this->createPage([
            'title' => 'Contact',
            'slug' => 'contact',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/pages?q=About')
            ->assertOk()
            ->assertSee('About us')
            ->assertDontSee('Contact');

        $this->actingAs($admin, 'admin')
            ->get('/admin/pages?status='.Page::STATUS_PUBLISHED)
            ->assertOk()
            ->assertSee('Contact')
            ->assertDontSee('About us');
    }

    public function test_preview_shows_draft_pages_with_watermark(): void
    {
        $admin = $this->adminUser();
        $draft = $this->createPage([
            'title' => 'Coming soon',
            'slug' => 'coming-soon',
            'content' => '<p>Draft body copy</p>',
            'status' => Page::STATUS_DRAFT,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/pages/'.$draft->id.'/preview')
            ->assertOk()
            ->assertSee('PREVIEW — DRAFT')
            ->assertSee('Coming soon')
            ->assertSee('<p>Draft body copy</p>', false);
    }

    public function test_preview_shows_published_pages_without_draft_watermark(): void
    {
        $admin = $this->adminUser();
        $published = $this->createPage([
            'title' => 'Contact',
            'slug' => 'contact',
            'content' => '<p>Published body copy</p>',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/pages/'.$published->id.'/preview')
            ->assertOk()
            ->assertSee('Contact')
            ->assertSee('<p>Published body copy</p>', false)
            ->assertDontSee('PREVIEW — DRAFT');
    }

    private function createPage(array $attributes = []): Page
    {
        return Page::query()->create([
            'title' => 'Untitled',
            'slug' => 'untitled-'.uniqid(),
            'status' => Page::STATUS_DRAFT,
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
                    'module' => 'package/page',
                    'group' => 'page',
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
