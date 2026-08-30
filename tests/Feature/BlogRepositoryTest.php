<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Packages\Blog\Models\Category;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Blog\Repositories\CategoryRepository;
use Sitewyn\Packages\Blog\Repositories\PostRepository;
use Sitewyn\Packages\Blog\Repositories\TagRepository;
use Tests\TestCase;

class BlogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_repository_can_create_find_update_and_delete_posts(): void
    {
        $repository = new PostRepository;

        $post = $repository->create([
            'title' => 'First post',
            'slug' => 'first-post',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $this->assertSame(Post::STATUS_PUBLISHED, $post->status);
        $this->assertSame($post->id, $repository->find($post->id)?->id);
        $this->assertSame($post->id, $repository->findBySlug('first-post')?->id);

        $updated = $repository->update($post, ['title' => 'Renamed post']);

        $this->assertSame('Renamed post', $updated->title);
        $this->assertDatabaseHas('posts', [
            'slug' => 'first-post',
            'title' => 'Renamed post',
        ]);

        $repository->delete($post);

        $this->assertNull($repository->find($post->id));
        $this->assertDatabaseMissing('posts', ['slug' => 'first-post']);
    }

    public function test_post_repository_creates_draft_posts_by_default(): void
    {
        $repository = new PostRepository;

        $post = $repository->create([
            'title' => 'Draft post',
            'slug' => 'draft-post',
        ]);

        $this->assertSame(Post::STATUS_DRAFT, $post->status);
        $this->assertNull($post->category_id);
    }

    public function test_post_repository_can_filter_by_status(): void
    {
        $repository = new PostRepository;

        $published = $repository->create([
            'title' => 'Published post',
            'slug' => 'published-post',
            'status' => Post::STATUS_PUBLISHED,
        ]);
        $repository->create([
            'title' => 'Draft post',
            'slug' => 'draft-post',
        ]);

        $this->assertCount(2, $repository->all());
        $this->assertSame([$published->id], $repository->byStatus(Post::STATUS_PUBLISHED)->pluck('id')->all());
    }

    public function test_post_repository_can_filter_by_category(): void
    {
        $repository = new PostRepository;
        $news = Category::query()->create(['name' => 'News', 'slug' => 'news']);
        $tech = Category::query()->create(['name' => 'Technology', 'slug' => 'technology']);

        $inNews = $repository->create([
            'title' => 'News post',
            'slug' => 'news-post',
            'category_id' => $news->id,
        ]);
        $inTech = $repository->create([
            'title' => 'Tech post',
            'slug' => 'tech-post',
            'category_id' => $tech->id,
        ]);
        $orphan = $repository->create([
            'title' => 'Uncategorized post',
            'slug' => 'uncategorized-post',
        ]);

        $this->assertSame([$inNews->id], $repository->inCategory($news->id)->pluck('id')->all());
        $this->assertSame([$inTech->id], $repository->inCategory($tech->id)->pluck('id')->all());
        $this->assertSame([$orphan->id], $repository->inCategory(null)->pluck('id')->all());
    }

    public function test_post_repository_can_search_by_title(): void
    {
        $repository = new PostRepository;
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news']);

        $match = $repository->create([
            'title' => 'Laravel tips',
            'slug' => 'laravel-tips',
            'status' => Post::STATUS_PUBLISHED,
            'category_id' => $category->id,
        ]);
        $repository->create([
            'title' => 'PHP tricks',
            'slug' => 'php-tricks',
            'status' => Post::STATUS_PUBLISHED,
        ]);
        $repository->create([
            'title' => 'Laravel draft',
            'slug' => 'laravel-draft',
            'status' => Post::STATUS_DRAFT,
            'category_id' => $category->id,
        ]);

        $this->assertSame([$match->id], $repository->search('tips')->pluck('id')->all());
        $this->assertCount(2, $repository->search('Laravel'));
        $this->assertSame([$match->id], $repository->search('Laravel', Post::STATUS_PUBLISHED)->pluck('id')->all());
        $this->assertSame([$match->id], $repository->search('Laravel', Post::STATUS_PUBLISHED, $category->id)->pluck('id')->all());
        $this->assertSame([], $repository->search('Missing')->pluck('id')->all());
    }

    public function test_category_repository_can_create_and_query_category_tree(): void
    {
        $repository = new CategoryRepository;

        $news = $repository->create(['name' => 'News', 'slug' => 'news']);
        $tech = $repository->create([
            'name' => 'Technology',
            'slug' => 'technology',
            'parent_id' => $news->id,
        ]);

        $this->assertSame($news->id, $repository->find($tech->id)?->parent?->id);
        $this->assertSame([$tech->id], $repository->childrenOf($news->id)->pluck('id')->all());
        $this->assertSame([$news->id], $repository->childrenOf(null)->pluck('id')->all());
        $this->assertSame($tech->id, $repository->findBySlug('technology')?->id);

        $updated = $repository->update($tech, ['description' => 'All about tech.']);

        $this->assertSame('All about tech.', $updated->description);

        $repository->delete($tech);

        $this->assertNull($repository->find($tech->id));
    }

    public function test_category_repository_can_search_by_name(): void
    {
        $repository = new CategoryRepository;
        $news = $repository->create(['name' => 'News', 'slug' => 'news']);
        $lifestyle = $repository->create(['name' => 'Lifestyle', 'slug' => 'lifestyle']);
        $repository->create([
            'name' => 'Technology',
            'slug' => 'technology',
            'parent_id' => $news->id,
        ]);

        $this->assertSame(['Technology'], $repository->searchByName('Tech', $news->id)->pluck('name')->all());
        $this->assertSame([], $repository->searchByName('Tech', $lifestyle->id)->pluck('name')->all());
        $this->assertSame(['Lifestyle', 'News', 'Technology'], $repository->searchByName('e')->pluck('name')->all());
        $this->assertSame([], $repository->searchByName('Missing')->pluck('name')->all());
    }

    public function test_tag_repository_can_crud_and_search_by_name(): void
    {
        $repository = new TagRepository;

        $php = $repository->create(['name' => 'PHP', 'slug' => 'php']);
        $repository->create(['name' => 'Laravel', 'slug' => 'laravel']);

        $this->assertSame(['Laravel', 'PHP'], $repository->all()->pluck('name')->all());
        $this->assertSame($php->id, $repository->findBySlug('php')?->id);
        $this->assertSame(['PHP'], $repository->searchByName('ph')->pluck('name')->all());
        $this->assertSame([], $repository->searchByName('Missing')->pluck('name')->all());

        $renamed = $repository->update($php, ['name' => 'PHP 8']);

        $this->assertSame('PHP 8', $renamed->name);
        $this->assertDatabaseHas('tags', ['slug' => 'php', 'name' => 'PHP 8']);

        $repository->delete($php);

        $this->assertNull($repository->find($php->id));
        $this->assertDatabaseMissing('tags', ['slug' => 'php']);
    }
}
