<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Support\SlugService;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Blog\Repositories\PostRepository;
use Sitewyn\Packages\Page\Models\Page;
use Sitewyn\Packages\Page\Repositories\PageRepository;
use Tests\TestCase;

class SlugServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_converts_vietnamese_titles_to_ascii_slugs(): void
    {
        $service = new SlugService;

        $this->assertSame('bai-viet-dau-tien', $service->generate('Bài viết đầu tiên'));
        $this->assertSame('dat-hang-thanh-toan', $service->generate('Đặt hàng & Thanh toán!'));
    }

    public function test_generate_falls_back_when_source_produces_empty_slug(): void
    {
        $service = new SlugService;

        $this->assertSame('untitled', $service->generate(''));
        $this->assertSame('untitled', $service->generate('   '));
        $this->assertSame('untitled', $service->generate('!!!'));
    }

    public function test_unique_for_returns_slug_unchanged_when_available(): void
    {
        $service = new SlugService;

        $this->assertSame('fresh-slug', $service->uniqueFor('fresh-slug', ['pages', 'posts']));
    }

    public function test_unique_for_appends_suffixes_within_the_same_table(): void
    {
        Page::query()->create(['title' => 'About us', 'slug' => 'about-us']);
        Page::query()->create(['title' => 'About us again', 'slug' => 'about-us-2']);

        $service = new SlugService;

        $this->assertSame('about-us-3', $service->uniqueFor('about-us', ['pages']));
    }

    public function test_unique_for_checks_across_tables(): void
    {
        Page::query()->create(['title' => 'About us', 'slug' => 'about-us']);
        Post::query()->create(['title' => 'About us', 'slug' => 'about-us-2']);

        $service = new SlugService;

        $this->assertSame('about-us-3', $service->uniqueFor('about-us', ['pages', 'posts']));
    }

    public function test_unique_for_ignores_the_given_record_when_updating(): void
    {
        $page = Page::query()->create(['title' => 'About us', 'slug' => 'about-us']);
        Page::query()->create(['title' => 'About us again', 'slug' => 'about-us-2']);

        $service = new SlugService;

        $this->assertSame('about-us', $service->uniqueFor('about-us', ['pages', 'posts'], $page->id));
        $this->assertSame('about-us-3', $service->uniqueFor('about-us', ['pages', 'posts']));
    }

    public function test_unique_for_only_ignores_the_own_table_record(): void
    {
        Page::query()->create(['title' => 'About us', 'slug' => 'about-us']);
        Post::query()->create(['title' => 'Renamed post', 'slug' => 'renamed-post']);

        $service = new SlugService;

        $this->assertSame('about-us', $service->uniqueFor('about-us', ['pages', 'posts'], 1, 'pages'));
        $this->assertSame('renamed-post-2', $service->uniqueFor('renamed-post', ['pages', 'posts'], 1, 'pages'));
    }

    public function test_generate_unique_combines_generate_and_unique_for(): void
    {
        $service = new SlugService;

        $this->assertSame('untitled', $service->generateUnique('', ['pages', 'posts']));

        Page::query()->create(['title' => 'Untitled', 'slug' => 'untitled']);

        $this->assertSame('untitled-2', $service->generateUnique('Untitled', ['pages', 'posts']));
    }

    public function test_page_repository_generates_slug_from_title_when_missing(): void
    {
        $repository = new PageRepository;

        $page = $repository->create(['title' => 'Bài viết đầu tiên']);

        $this->assertSame('bai-viet-dau-tien', $page->slug);
    }

    public function test_page_repository_suffixes_duplicate_page_slugs(): void
    {
        $repository = new PageRepository;

        $first = $repository->create(['title' => 'About us']);
        $second = $repository->create(['title' => 'About us']);

        $this->assertSame('about-us', $first->slug);
        $this->assertSame('about-us-2', $second->slug);
    }

    public function test_post_repository_suffixes_slug_when_page_uses_it_first(): void
    {
        $pageRepository = new PageRepository;
        $postRepository = new PostRepository;

        $page = $pageRepository->create(['title' => 'Hướng dẫn', 'slug' => 'huong-dan']);
        $post = $postRepository->create(['title' => 'Hướng dẫn']);

        $this->assertSame('huong-dan', $page->slug);
        $this->assertSame('huong-dan-2', $post->slug);
    }

    public function test_post_repository_generates_and_suffixes_slugs_across_tables(): void
    {
        $pageRepository = new PageRepository;
        $postRepository = new PostRepository;

        $post = $postRepository->create(['title' => 'Tin tức']);
        $page = $pageRepository->create(['title' => 'Tin tức']);
        $secondPost = $postRepository->create(['title' => 'Tin tức']);

        $this->assertSame('tin-tuc', $post->slug);
        $this->assertSame('tin-tuc-2', $page->slug);
        $this->assertSame('tin-tuc-3', $secondPost->slug);
    }

    public function test_page_repository_keeps_existing_slug_when_update_has_no_slug(): void
    {
        $repository = new PageRepository;

        $page = $repository->create(['title' => 'About us', 'slug' => 'about-us']);

        $updated = $repository->update($page, ['title' => 'About Sitewyn']);

        $this->assertSame('about-us', $updated->slug);
    }

    public function test_page_repository_keeps_own_slug_when_resubmitted_on_update(): void
    {
        $repository = new PageRepository;

        $page = $repository->create(['title' => 'About us', 'slug' => 'about-us']);

        $updated = $repository->update($page, ['title' => 'About us', 'slug' => 'about-us']);

        $this->assertSame('about-us', $updated->slug);
    }

    public function test_page_repository_suffixes_slug_on_update_when_taken_by_another_record(): void
    {
        $repository = new PageRepository;
        $postRepository = new PostRepository;

        $page = $repository->create(['title' => 'About us', 'slug' => 'about-us']);
        $postRepository->create(['title' => 'Renamed post', 'slug' => 'renamed-post']);

        $updated = $repository->update($page, ['slug' => 'renamed-post']);

        $this->assertSame('renamed-post-2', $updated->slug);
    }

    public function test_post_repository_suffixes_slug_on_update_when_taken_by_a_page(): void
    {
        $pageRepository = new PageRepository;
        $postRepository = new PostRepository;

        $post = $postRepository->create(['title' => 'First post', 'slug' => 'first-post']);
        $pageRepository->create(['title' => 'Contact', 'slug' => 'contact']);

        $updated = $postRepository->update($post, ['slug' => 'contact']);

        $this->assertSame('contact-2', $updated->slug);
    }
}
