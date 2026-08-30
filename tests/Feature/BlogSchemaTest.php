<?php

namespace Tests\Feature;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Sitewyn\Packages\Blog\Models\Category;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Blog\Models\Tag;
use Tests\TestCase;

class BlogSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_tables_are_created(): void
    {
        $this->assertTrue(Schema::hasTable('categories'));
        $this->assertTrue(Schema::hasTable('posts'));
        $this->assertTrue(Schema::hasTable('tags'));
        $this->assertTrue(Schema::hasTable('post_tag'));
    }

    public function test_categories_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('categories', [
            'id',
            'name',
            'slug',
            'description',
            'parent_id',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_posts_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('posts', [
            'id',
            'title',
            'slug',
            'content',
            'seo_title',
            'seo_description',
            'status',
            'category_id',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_tags_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('tags', [
            'id',
            'name',
            'slug',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_slug_columns_have_unique_indexes(): void
    {
        foreach (['categories', 'posts', 'tags'] as $table) {
            $index = collect(Schema::getIndexes($table))
                ->first(fn (array $index): bool => $index['columns'] === ['slug']);

            $this->assertNotNull($index, "Failed asserting that [{$table}] has a slug index.");
            $this->assertTrue((bool) $index['unique'], "Failed asserting that [{$table}.slug] is unique.");
        }
    }

    public function test_post_status_is_indexed(): void
    {
        $index = collect(Schema::getIndexes('posts'))
            ->first(fn (array $index): bool => $index['columns'] === ['status']);

        $this->assertNotNull($index);
    }

    public function test_post_status_defaults_to_draft(): void
    {
        DB::table('posts')->insert([
            'title' => 'Hello world',
            'slug' => 'hello-world',
        ]);

        $this->assertSame(Post::STATUS_DRAFT, DB::table('posts')->value('status'));
    }

    public function test_post_tag_pivot_has_composite_primary_key(): void
    {
        $index = collect(Schema::getIndexes('post_tag'))
            ->first(fn (array $index): bool => (bool) $index['primary']);

        $this->assertNotNull($index);
        $this->assertSame(['post_id', 'tag_id'], $index['columns']);
    }

    public function test_post_model_can_be_created_with_fillable_attributes(): void
    {
        $post = Post::query()->create([
            'title' => 'First post',
            'slug' => 'first-post',
            'content' => '<p>Hello world.</p>',
            'seo_title' => 'First post - Sitewyn',
            'seo_description' => 'The first blog post.',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $this->assertSame('First post', $post->title);
        $this->assertSame(Post::STATUS_PUBLISHED, $post->status);
        $this->assertDatabaseHas('posts', [
            'slug' => 'first-post',
            'status' => Post::STATUS_PUBLISHED,
        ]);
    }

    public function test_post_supports_nullable_content_seo_and_category(): void
    {
        $post = Post::query()->create([
            'title' => 'Draft post',
            'slug' => 'draft-post',
        ]);

        $this->assertNull($post->content);
        $this->assertNull($post->seo_title);
        $this->assertNull($post->seo_description);
        $this->assertNull($post->category_id);
    }

    public function test_post_tag_pivot_rejects_duplicate_pairs(): void
    {
        $tag = Tag::query()->create(['name' => 'PHP', 'slug' => 'php']);
        $post = Post::query()->create(['title' => 'First post', 'slug' => 'first-post']);

        $post->tags()->attach($tag);

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('post_tag')->insert([
            'post_id' => $post->getKey(),
            'tag_id' => $tag->getKey(),
        ]);
    }

    public function test_post_relationships_resolve_in_both_directions(): void
    {
        $category = Category::query()->create([
            'name' => 'Technology',
            'slug' => 'technology',
        ]);

        $post = Post::query()->create([
            'title' => 'First post',
            'slug' => 'first-post',
            'category_id' => $category->getKey(),
        ]);

        $tag = Tag::query()->create([
            'name' => 'PHP',
            'slug' => 'php',
        ]);

        $post->tags()->attach($tag);

        $this->assertTrue($post->category->is($category));
        $this->assertTrue($post->tags->sole()->is($tag));
        $this->assertTrue($category->posts->sole()->is($post));
        $this->assertTrue($tag->posts->sole()->is($post));
    }

    public function test_category_relationships_resolve_in_both_directions(): void
    {
        $parent = Category::query()->create([
            'name' => 'News',
            'slug' => 'news',
        ]);

        $child = Category::query()->create([
            'name' => 'Technology',
            'slug' => 'technology',
            'parent_id' => $parent->getKey(),
        ]);

        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($parent->children->sole()->is($child));
    }

    public function test_deleting_category_nulls_post_category_id(): void
    {
        $category = Category::query()->create([
            'name' => 'Temporary',
            'slug' => 'temporary',
        ]);

        $post = Post::query()->create([
            'title' => 'Orphan post',
            'slug' => 'orphan-post',
            'category_id' => $category->getKey(),
        ]);

        $category->delete();

        $this->assertNull($post->fresh()->category_id);
    }

    public function test_deleting_parent_category_moves_children_to_root(): void
    {
        $parent = Category::query()->create([
            'name' => 'News',
            'slug' => 'news',
        ]);

        $child = Category::query()->create([
            'name' => 'Technology',
            'slug' => 'technology',
            'parent_id' => $parent->getKey(),
        ]);

        $parent->delete();

        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_deleting_post_detaches_pivot_rows(): void
    {
        $tag = Tag::query()->create(['name' => 'PHP', 'slug' => 'php']);
        $post = Post::query()->create(['title' => 'First post', 'slug' => 'first-post']);

        $post->tags()->attach($tag);
        $post->delete();

        $this->assertDatabaseCount('post_tag', 0);
        $this->assertDatabaseHas('tags', ['slug' => 'php']);
    }
}
