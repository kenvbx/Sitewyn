<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Support\RobotsTxt;
use Sitewyn\Core\Base\Support\SitemapRegistry;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

class SeoFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_published_pages_and_posts_with_lastmod(): void
    {
        $page = $this->createPage([
            'title' => 'About us',
            'slug' => 'about-us',
            'status' => Page::STATUS_PUBLISHED,
        ]);
        $post = $this->createPost([
            'title' => 'Hello world',
            'slug' => 'hello-world',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.url('/about-us').'</loc>', false)
            ->assertSee('<loc>'.url('/blog/hello-world').'</loc>', false)
            ->assertSee('<lastmod>'.$page->updated_at->format(DATE_ATOM).'</lastmod>', false)
            ->assertSee('<lastmod>'.$post->updated_at->format(DATE_ATOM).'</lastmod>', false);
    }

    public function test_draft_pages_and_posts_are_missing_from_the_sitemap(): void
    {
        $this->createPage(['title' => 'Coming soon', 'slug' => 'coming-soon']);
        $this->createPost(['title' => 'Secret draft', 'slug' => 'secret-draft']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee(url('/coming-soon'), false)
            ->assertDontSee(url('/blog/secret-draft'), false);
    }

    public function test_sitemap_stays_valid_xml_when_nothing_is_published(): void
    {
        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false)
            ->assertDontSee('<url>', false);

        $xml = simplexml_load_string((string) $response->getContent());

        $this->assertNotFalse($xml);
        $this->assertSame('urlset', $xml->getName());
    }

    public function test_sitemap_picks_up_newly_published_content_on_the_next_request(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee('<url>', false);

        $this->createPage([
            'title' => 'Fresh page',
            'slug' => 'fresh-page',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        // No cache sits between publishing and the sitemap: the very next
        // request must already list the new content (P5-08 acceptance).
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.url('/fresh-page').'</loc>', false);
    }

    public function test_sitemap_registry_dedupes_entries_by_loc_and_drops_blank_locs(): void
    {
        $registry = new SitemapRegistry;
        $registry->register(fn (): array => [
            ['loc' => url('/about-us'), 'lastmod' => null],
            ['loc' => '', 'lastmod' => null],
        ]);
        $registry->register(fn (): array => [
            ['loc' => url('/about-us'), 'lastmod' => now()],
        ]);

        $entries = $registry->entries();

        $this->assertSame([url('/about-us')], array_column($entries, 'loc'));
    }

    public function test_robots_txt_serves_default_when_unconfigured(): void
    {
        $response = $this->get('/robots.txt');

        $this->assertSame(RobotsTxt::DEFAULT_CONTENT, $response->getContent());
        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function test_saved_robots_txt_setting_is_served(): void
    {
        // The global TrimStrings middleware strips trailing whitespace, so the
        // stored body is saved (and served) without the trailing newline.
        $this->actingAsSuperAdmin()
            ->put('/admin/settings', [
                'site_name' => 'Sitewyn',
                'robots_txt' => "User-agent: *\nDisallow: /",
            ])
            ->assertRedirect('/admin/settings');

        $this->assertDatabaseHas('settings', [
            'key' => 'robots_txt',
            'value' => "User-agent: *\nDisallow: /",
            'group' => 'general',
        ]);

        $response = $this->get('/robots.txt');

        $this->assertSame("User-agent: *\nDisallow: /", $response->getContent());
    }

    public function test_settings_hub_presents_permalink_settings(): void
    {
        $this->actingAsSuperAdmin()
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Permalink')
            ->assertSee('View and update your permalink settings');
    }

    public function test_robots_txt_setting_rejects_more_than_2000_characters(): void
    {
        $this->actingAsSuperAdmin()
            ->from('/admin/settings')
            ->put('/admin/settings', [
                'site_name' => 'Sitewyn',
                'robots_txt' => str_repeat('a', 2001),
            ])
            ->assertRedirect('/admin/settings')
            ->assertSessionHasErrors(['robots_txt']);

        $this->assertDatabaseMissing('settings', ['key' => 'robots_txt']);
        $this->assertSame(RobotsTxt::DEFAULT_CONTENT, $this->get('/robots.txt')->getContent());
    }

    public function test_content_named_like_the_seo_files_cannot_hijack_them(): void
    {
        $this->createPage([
            'title' => 'Sitemap impostor',
            'slug' => 'sitemap.xml',
            'status' => Page::STATUS_PUBLISHED,
        ]);
        $this->createPost([
            'title' => 'Robots impostor',
            'slug' => 'robots.txt',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false)
            ->assertDontSee('<h1>Sitemap impostor</h1>', false);

        $this->assertNotFalse(simplexml_load_string((string) $response->getContent()));

        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertDontSee('<h1>', false);
    }

    private function actingAsSuperAdmin(): self
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        return $this->actingAs($admin, 'admin');
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

    private function createPost(array $attributes = []): Post
    {
        return Post::query()->create([
            'title' => 'Untitled',
            'slug' => 'untitled-'.uniqid(),
            'status' => Post::STATUS_DRAFT,
            ...$attributes,
        ]);
    }
}
