<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Packages\Page\Models\Page;
use Sitewyn\Packages\Page\Repositories\PageRepository;
use Tests\TestCase;

class PageRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_repository_can_create_find_update_and_delete_pages(): void
    {
        $repository = new PageRepository;

        $page = $repository->create([
            'title' => 'About us',
            'slug' => 'about-us',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        $this->assertSame(Page::STATUS_PUBLISHED, $page->status);
        $this->assertSame($page->id, $repository->find($page->id)?->id);
        $this->assertSame($page->id, $repository->findBySlug('about-us')?->id);

        $updated = $repository->update($page, ['title' => 'About Sitewyn']);

        $this->assertSame('About Sitewyn', $updated->title);
        $this->assertDatabaseHas('pages', [
            'slug' => 'about-us',
            'title' => 'About Sitewyn',
        ]);

        $repository->delete($page);

        $this->assertNull($repository->find($page->id));
        $this->assertDatabaseMissing('pages', ['slug' => 'about-us']);
    }

    public function test_page_repository_creates_draft_pages_by_default(): void
    {
        $repository = new PageRepository;

        $page = $repository->create([
            'title' => 'Coming soon',
            'slug' => 'coming-soon',
        ]);

        $this->assertSame(Page::STATUS_DRAFT, $page->status);
    }

    public function test_page_repository_can_filter_by_status(): void
    {
        $repository = new PageRepository;

        $published = $repository->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'status' => Page::STATUS_PUBLISHED,
        ]);
        $repository->create([
            'title' => 'Coming soon',
            'slug' => 'coming-soon',
            'status' => Page::STATUS_DRAFT,
        ]);

        $this->assertCount(2, $repository->all());
        $this->assertSame([$published->id], $repository->byStatus(Page::STATUS_PUBLISHED)->pluck('id')->all());
    }

    public function test_page_repository_can_search_by_title(): void
    {
        $repository = new PageRepository;

        $match = $repository->create([
            'title' => 'Homepage Banner',
            'slug' => 'homepage-banner',
            'status' => Page::STATUS_PUBLISHED,
        ]);
        $repository->create([
            'title' => 'Terms of Service',
            'slug' => 'terms-of-service',
            'status' => Page::STATUS_PUBLISHED,
        ]);
        $repository->create([
            'title' => 'Homepage Draft',
            'slug' => 'homepage-draft',
            'status' => Page::STATUS_DRAFT,
        ]);

        $this->assertSame([$match->id], $repository->search('Banner')->pluck('id')->all());
        $this->assertCount(2, $repository->search('Homepage'));
        $this->assertSame([$match->id], $repository->search('Homepage', Page::STATUS_PUBLISHED)->pluck('id')->all());
        $this->assertSame([], $repository->search('Missing')->pluck('id')->all());
    }
}
