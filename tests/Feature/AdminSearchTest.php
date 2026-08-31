<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/search?q=hello')
            ->assertRedirect('/admin/login');
    }

    public function test_search_returns_matching_pages_posts_and_users(): void
    {
        $admin = User::factory()->create([
            'name' => 'Hello Admin',
            'email' => 'hello-admin@example.com',
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $page = Page::query()->create([
            'title' => 'Hello world page',
            'slug' => 'hello-world-page',
            'status' => Page::STATUS_DRAFT,
        ]);
        $post = Post::query()->create([
            'title' => 'Hello launch post',
            'slug' => 'hello-launch-post',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/search?q=hello')
            ->assertOk()
            ->assertJsonStructure([
                'groups' => [
                    ['label', 'items' => [['title', 'subtitle', 'url', 'icon']]],
                ],
            ]);

        $groups = collect($response->json('groups'))->keyBy('label');

        $pageItems = $groups->get('Pages')['items'];
        $postItems = $groups->get('Posts')['items'];
        $userItems = $groups->get('Users')['items'];

        $this->assertCount(1, $pageItems);
        $this->assertSame('Hello world page', $pageItems[0]['title']);
        $this->assertSame('/admin/pages/'.$page->id.'/edit', $pageItems[0]['url']);
        $this->assertSame('/admin/pages/'.$page->id.'/edit', $pageItems[0]['subtitle']);
        $this->assertSame('page', $pageItems[0]['icon']);

        $this->assertSame('Hello launch post', $postItems[0]['title']);
        $this->assertSame('/admin/posts/'.$post->id.'/edit', $postItems[0]['url']);
        $this->assertSame('post', $postItems[0]['icon']);

        $this->assertCount(1, $userItems);
        $this->assertSame('Hello Admin', $userItems[0]['title']);
        // Team members link to the team surface edit form.
        $this->assertSame('/admin/system/users/'.$admin->id.'/edit', $userItems[0]['url']);
        $this->assertSame('users', $userItems[0]['icon']);

        // Credentials must never be part of the search payload.
        $this->assertStringNotContainsString('password', $response->getContent());
    }

    public function test_search_finds_users_by_email(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $found = User::factory()->create([
            'name' => 'Random Person',
            'email' => 'findme@example.com',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/search?q=findme')
            ->assertOk();

        $userItems = collect($response->json('groups'))
            ->firstWhere('label', 'Users')['items'];

        $this->assertCount(1, $userItems);
        $this->assertSame('Random Person', $userItems[0]['title']);
        // Outside users link to the outside surface edit form.
        $this->assertSame('/admin/users/'.$found->id.'/edit', $userItems[0]['url']);
    }

    public function test_empty_query_returns_only_quick_links(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/search')
            ->assertOk();

        $groups = $response->json('groups');

        $this->assertCount(1, $groups);
        $this->assertSame('Quick links', $groups[0]['label']);

        $labels = collect($groups[0]['items'])->pluck('title')->values()->all();

        $this->assertSame([
            'Dashboard',
            'Media',
            'Menus',
            'Widgets',
            'Plugins',
            'Settings',
            'Audit Logs',
        ], $labels);

        foreach ($groups[0]['items'] as $item) {
            $this->assertSame($item['subtitle'], $item['url']);
            $this->assertStringStartsWith('/admin', $item['url']);
        }
    }

    public function test_result_groups_are_limited_to_five(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        foreach (range(1, 6) as $index) {
            Page::query()->create([
                'title' => 'Limit page '.$index,
                'slug' => 'limit-page-'.$index,
                'status' => Page::STATUS_DRAFT,
            ]);
        }

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/search?q=Limit page')
            ->assertOk();

        $pages = collect($response->json('groups'))->firstWhere('label', 'Pages');

        $this->assertCount(5, $pages['items']);
    }

    public function test_quick_links_respect_permissions(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'admin')
            ->get('/admin/search')
            ->assertOk();

        $groups = $response->json('groups');

        $this->assertCount(1, $groups);
        $this->assertSame('Quick links', $groups[0]['label']);
        $this->assertSame(['Dashboard'], collect($groups[0]['items'])->pluck('title')->all());
    }

    public function test_admin_header_renders_search_trigger_theme_toggle_and_view_website(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertOk()
            // Dark slate header chrome.
            ->assertSee('navbar-dark', false)
            ->assertSee('sitewyn-admin-header', false)
            // Search trigger (data attribute marker + endpoint URL).
            ->assertSee('data-admin-search', false)
            ->assertSee('data-admin-search-url', false)
            // View website link.
            ->assertSee('View website')
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener"', false)
            // Theme toggle button + persisted localStorage key.
            ->assertSee('data-admin-theme-toggle', false)
            ->assertSee('sitewyn-admin-theme', false)
            // Search modal markup.
            ->assertSee('id="admin-search-modal"', false)
            ->assertSee('Search pages, posts, users...', false)
            // Notifications MVP empty state.
            ->assertSee('No notifications yet.');
    }
}
