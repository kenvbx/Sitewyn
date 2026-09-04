<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Support\SettingStore;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Blog\Models\Tag;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

class PostFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_post_is_served_at_blog_slug_url(): void
    {
        $post = Post::query()->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
            'content' => '<p>Post body copy</p>',
            'status' => Post::STATUS_PUBLISHED,
            'featured_image' => '/storage/hello-world.jpg',
        ]);
        $tag = Tag::query()->create(['name' => 'News', 'slug' => 'news']);
        $post->tags()->attach($tag->id);

        $this->get('/blog/hello-world')
            ->assertOk()
            ->assertSee('<h1>Hello world</h1>', false)
            ->assertSee('<p>Post body copy</p>', false)
            ->assertSee('src="/storage/hello-world.jpg"', false)
            ->assertSee('News')
            ->assertSee($post->updated_at->format('F j, Y'));
    }

    public function test_draft_post_is_never_served_publicly(): void
    {
        Post::query()->create([
            'title' => 'Sneak peek',
            'slug' => 'sneak-peek',
            'content' => '<p>Draft body copy</p>',
        ]);

        $this->get('/blog/sneak-peek')->assertNotFound();
    }

    public function test_unknown_post_slug_returns_404(): void
    {
        $this->get('/blog/no-such-post')->assertNotFound();
    }

    public function test_page_slug_is_not_served_by_the_blog_route(): void
    {
        Page::query()->create([
            'title' => 'About us',
            'slug' => 'about-us',
            'content' => '<p>About body copy</p>',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        $this->get('/blog/about-us')->assertNotFound();
    }

    public function test_post_renders_seo_and_open_graph_meta_tags(): void
    {
        Post::query()->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
            'content' => '<p>Post body copy</p>',
            'status' => Post::STATUS_PUBLISHED,
            'seo_title' => 'Hello world on Sitewyn',
            'seo_description' => 'Post description for search engines.',
            'og_image' => '/storage/hello-card.jpg',
        ]);

        $this->get('/blog/hello-world')
            ->assertOk()
            ->assertSee('<title>Hello world on Sitewyn</title>', false)
            ->assertSee('<meta name="description" content="Post description for search engines." />', false)
            ->assertSee('<meta property="og:title" content="Hello world on Sitewyn" />', false)
            ->assertSee('<meta property="og:image" content="/storage/hello-card.jpg" />', false);
    }

    public function test_post_renders_configured_schema_markup(): void
    {
        app(SettingStore::class)->setMany([
            'blog_schema_enabled' => '1',
            'blog_schema_type' => 'NewsArticle',
        ]);

        Post::query()->create([
            'title' => 'Structured post',
            'slug' => 'structured-post',
            'content' => '<p>Post body copy</p>',
            'status' => Post::STATUS_PUBLISHED,
            'seo_description' => 'Structured data description.',
            'featured_image' => '/storage/structured.jpg',
        ]);

        $this->get('/blog/structured-post')
            ->assertOk()
            ->assertSee('<script type="application/ld+json">', false)
            ->assertSee('"@type":"NewsArticle"', false)
            ->assertSee('"headline":"Structured post"', false)
            ->assertSee('"description":"Structured data description."', false)
            ->assertSee('"image":"/storage/structured.jpg"', false);
    }

    public function test_post_schema_markup_can_be_disabled(): void
    {
        app(SettingStore::class)->setMany([
            'blog_schema_enabled' => '0',
        ]);

        Post::query()->create([
            'title' => 'No schema',
            'slug' => 'no-schema',
            'content' => '<p>Post body copy</p>',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $this->get('/blog/no-schema')
            ->assertOk()
            ->assertDontSee('application/ld+json', false);
    }

    public function test_post_heading_anchor_links_can_be_enabled(): void
    {
        app(SettingStore::class)->setMany([
            'blog_anchor_links_enabled' => '1',
        ]);

        Post::query()->create([
            'title' => 'Anchored post',
            'slug' => 'anchored-post',
            'content' => '<h2>Pricing</h2><h3 id="custom-id">FAQ</h3><pre><h2>Code heading</h2></pre>',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $this->get('/blog/anchored-post')
            ->assertOk()
            ->assertSee('<h2 id="pricing">Pricing</h2>', false)
            ->assertSee('<h3 id="custom-id">FAQ</h3>', false)
            ->assertSee('<pre><h2>Code heading</h2></pre>', false);
    }
}
