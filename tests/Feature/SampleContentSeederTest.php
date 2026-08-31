<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Sitewyn\Core\Base\Database\Seeders\SampleContentSeeder;
use Sitewyn\Core\Base\Models\Menu;
use Sitewyn\Core\Base\Models\MenuItem;
use Sitewyn\Packages\Blog\Models\Category;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Blog\Models\Tag;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

/**
 * Sample content seeder (run on demand, never from DatabaseSeeder):
 * demo pages, blog content, and the primary nav for previewing the
 * frontend. Re-running must never duplicate rows or overwrite edits.
 */
class SampleContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_five_published_pages_with_expected_slugs(): void
    {
        $this->seed(SampleContentSeeder::class);

        $this->assertSame(5, Page::query()->count());

        foreach (['about-us', 'contact', 'privacy-policy', 'terms-of-service', 'services'] as $slug) {
            $page = Page::query()->where('slug', $slug)->first();

            $this->assertNotNull($page, "Page [{$slug}] should exist after seeding.");
            $this->assertSame(Page::STATUS_PUBLISHED, $page->status);
            $this->assertStringContainsString('<h2>', (string) $page->content);
            $this->assertNotNull($page->seo_description);
        }
    }

    public function test_seeder_creates_categories_and_published_posts_with_synced_tags(): void
    {
        $this->seed(SampleContentSeeder::class);

        foreach (['news', 'tutorials', 'releases'] as $slug) {
            $this->assertDatabaseHas('categories', ['slug' => $slug]);
        }

        $this->assertSame(5, Post::query()->where('status', Post::STATUS_PUBLISHED)->count());

        foreach (['laravel', 'cms', 'php', 'testing', 'architecture'] as $slug) {
            $this->assertDatabaseHas('tags', ['slug' => $slug]);
        }

        Post::query()->with(['category', 'tags'])->get()->each(function (Post $post): void {
            $this->assertNotNull($post->category_id);
            $this->assertNotNull($post->category);
            $this->assertContains($post->tags->count(), [2, 3], "Post [{$post->slug}] should carry 2-3 tags.");
        });
    }

    public function test_seeder_creates_primary_menu_with_the_sample_pages_in_order(): void
    {
        $this->seed(SampleContentSeeder::class);

        $menu = Menu::query()->where('slug', 'main')->first();

        $this->assertNotNull($menu);
        $this->assertSame('Main', $menu->name);
        $this->assertSame(Menu::LOCATION_PRIMARY, $menu->location);

        $items = $menu->items;

        $this->assertCount(3, $items);
        $this->assertSame(['About us', 'Services', 'Contact'], $items->pluck('label')->all());
        $this->assertSame([0, 1, 2], $items->pluck('order')->all());

        $items->each(function (MenuItem $item): void {
            $this->assertSame(MenuItem::TYPE_PAGE, $item->type);
            $this->assertDatabaseHas('pages', ['id' => $item->target_id, 'status' => Page::STATUS_PUBLISHED]);
        });
    }

    public function test_seeding_twice_does_not_duplicate_or_overwrite_content(): void
    {
        $this->seed(SampleContentSeeder::class);

        $counts = $this->contentCounts();

        // Hand edits after the first run must survive a re-run untouched.
        Page::query()->where('slug', 'about-us')->firstOrFail()->update([
            'title' => 'About Sitewyn',
            'content' => '<p>Hand-edited about copy.</p>',
        ]);

        Post::query()->where('slug', 'understanding-laravel-middleware-execution-order')->firstOrFail()->update([
            'content' => '<p>Hand-edited post copy.</p>',
        ]);

        $this->seed(SampleContentSeeder::class);

        $this->assertSame($counts, $this->contentCounts());

        $page = Page::query()->where('slug', 'about-us')->firstOrFail();
        $this->assertSame('About Sitewyn', $page->title);
        $this->assertSame('<p>Hand-edited about copy.</p>', $page->content);

        $post = Post::query()->where('slug', 'understanding-laravel-middleware-execution-order')->firstOrFail();
        $this->assertSame('<p>Hand-edited post copy.</p>', $post->content);
    }

    public function test_seeded_pages_and_posts_are_served_on_the_frontend(): void
    {
        $this->seed(SampleContentSeeder::class);

        $this->get('/about-us')->assertOk();
        $this->get('/contact')->assertOk();
        $this->get('/services')->assertOk();

        $this->get('/blog/testing-feature-routes-against-in-memory-sqlite')
            ->assertOk()
            ->assertSee('<h1>Testing Feature Routes Against In-Memory SQLite</h1>', false);
    }

    /**
     * Row counts across every table the seeder touches — compared before
     * and after a second run to prove idempotency.
     *
     * @return array<string, int>
     */
    private function contentCounts(): array
    {
        return [
            'pages' => Page::query()->count(),
            'categories' => Category::query()->count(),
            'posts' => Post::query()->count(),
            'tags' => Tag::query()->count(),
            'menus' => Menu::query()->count(),
            'menu_items' => MenuItem::query()->count(),
            'post_tag' => (int) DB::table('post_tag')->count(),
        ];
    }
}
