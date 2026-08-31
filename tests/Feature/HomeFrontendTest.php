<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

/**
 * CMS front page (P5-02): GET / renders the active theme's frontend.home
 * with the latest published posts and pages instead of the Laravel welcome
 * view that used to occupy the route.
 */
class HomeFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_the_empty_state_without_content(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('No content yet. Sign in to the admin to create your first page or post.');
    }

    public function test_home_lists_published_posts_with_links_and_excerpts(): void
    {
        $post = Post::query()->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
            'content' => '<p>'.str_repeat('Lorem ipsum dolor sit amet. ', 20).'</p>',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Latest posts')
            ->assertSee('href="/blog/hello-world"', false)
            ->assertSee('Hello world')
            ->assertSee($post->updated_at->format('F j, Y'))
            // The teaser is the stripped, capped excerpt — never the raw
            // stored HTML.
            ->assertSee(Str::limit(trim(strip_tags((string) $post->content)), 160))
            ->assertDontSee(str_repeat('Lorem ipsum dolor sit amet. ', 20));
    }

    public function test_draft_posts_do_not_appear_on_the_home_page(): void
    {
        Post::query()->create([
            'title' => 'Sneak peek',
            'slug' => 'sneak-peek',
            'content' => '<p>Draft body copy</p>',
        ]);
        Post::query()->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
            'content' => '<p>Post body copy</p>',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Hello world')
            ->assertDontSee('Sneak peek');
    }

    public function test_home_lists_published_pages_as_a_small_section(): void
    {
        Post::query()->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
            'content' => '<p>Post body copy</p>',
            'status' => Post::STATUS_PUBLISHED,
        ]);
        Page::query()->create([
            'title' => 'About us',
            'slug' => 'about-us',
            'content' => '<p>About body copy</p>',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Pages')
            ->assertSee('href="/about-us"', false)
            ->assertSee('About us');
    }

    public function test_home_no_longer_serves_the_laravel_welcome_view(): void
    {
        Post::query()->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
            'content' => '<p>Post body copy</p>',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $this->get('/')
            ->assertOk()
            // Theme markup rendered, and the post link proves CMS content —
            // the welcome view is gone. "View changelog" is unique to the
            // welcome view; the bare word 'Laravel' is NOT assertable here,
            // it is the default APP_NAME the site title falls back to.
            ->assertSee('Proudly powered by')
            ->assertSee('href="/blog/hello-world"', false)
            ->assertDontSee('View changelog');
    }
}
