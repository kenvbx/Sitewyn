<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

class PageSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_table_is_created(): void
    {
        $this->assertTrue(Schema::hasTable('pages'));
    }

    public function test_pages_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('pages', [
            'id',
            'title',
            'slug',
            'content',
            'seo_title',
            'seo_description',
            'status',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_pages_table_has_unique_slug_index_and_status_index(): void
    {
        $indexes = Schema::getIndexes('pages');

        $slugIndex = collect($indexes)->first(fn (array $index): bool => $index['columns'] === ['slug']);
        $statusIndex = collect($indexes)->first(fn (array $index): bool => $index['columns'] === ['status']);

        $this->assertNotNull($slugIndex);
        $this->assertTrue((bool) $slugIndex['unique']);

        $this->assertNotNull($statusIndex);
    }

    public function test_status_defaults_to_draft(): void
    {
        DB::table('pages')->insert([
            'title' => 'About us',
            'slug' => 'about-us',
        ]);

        $this->assertSame(Page::STATUS_DRAFT, DB::table('pages')->value('status'));
    }

    public function test_page_model_can_be_created_with_fillable_attributes(): void
    {
        $page = Page::query()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'content' => '<p>Contact us.</p>',
            'seo_title' => 'Contact - Sitewyn',
            'seo_description' => 'How to reach us.',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        $this->assertSame('Contact', $page->title);
        $this->assertSame(Page::STATUS_PUBLISHED, $page->status);
        $this->assertDatabaseHas('pages', [
            'slug' => 'contact',
            'status' => Page::STATUS_PUBLISHED,
        ]);
    }

    public function test_page_model_supports_nullable_content_and_seo_columns(): void
    {
        $page = Page::query()->create([
            'title' => 'Draft page',
            'slug' => 'draft-page',
        ]);

        $this->assertNull($page->content);
        $this->assertNull($page->seo_title);
        $this->assertNull($page->seo_description);
    }
}
