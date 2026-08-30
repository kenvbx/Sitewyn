<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

class PageFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_page_is_served_at_its_slug_url(): void
    {
        $this->createPage([
            'title' => 'About us',
            'slug' => 'about-us',
            'content' => '<p>About body copy</p>',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        $this->get('/about-us')
            ->assertOk()
            ->assertSee('<h1>About us</h1>', false)
            ->assertSee('<p>About body copy</p>', false);
    }

    public function test_draft_page_is_never_served_publicly(): void
    {
        $this->createPage([
            'title' => 'Coming soon',
            'slug' => 'coming-soon',
            'content' => '<p>Draft body copy</p>',
        ]);

        $this->get('/coming-soon')->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/no-such-page')->assertNotFound();
    }

    public function test_post_slug_is_not_served_by_the_page_route(): void
    {
        Post::query()->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
            'content' => '<p>Post body copy</p>',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $this->get('/hello-world')->assertNotFound();
    }

    public function test_reserved_blog_segment_is_not_swallowed_by_the_page_route(): void
    {
        $this->get('/blog')->assertNotFound();
    }

    public function test_page_renders_seo_and_open_graph_meta_tags(): void
    {
        $this->createPage([
            'title' => 'About us',
            'slug' => 'about-us',
            'content' => '<p>About body copy</p>',
            'status' => Page::STATUS_PUBLISHED,
            'seo_title' => 'About Sitewyn',
            'seo_description' => 'About page description.',
            'og_image' => '/storage/about-card.jpg',
        ]);

        $this->get('/about-us')
            ->assertOk()
            ->assertSee('<title>About Sitewyn</title>', false)
            ->assertSee('<meta name="description" content="About page description." />', false)
            ->assertSee('<meta property="og:title" content="About Sitewyn" />', false)
            ->assertSee('<meta property="og:image" content="/storage/about-card.jpg" />', false);
    }

    public function test_page_title_falls_back_to_page_title_without_seo_title(): void
    {
        $this->createPage([
            'title' => 'Contact',
            'slug' => 'contact',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('<title>Contact</title>', false);
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
}
