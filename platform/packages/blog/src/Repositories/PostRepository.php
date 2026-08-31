<?php

namespace Sitewyn\Packages\Blog\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Sitewyn\Core\Base\Support\SlugService;
use Sitewyn\Packages\Blog\Models\Post;

class PostRepository
{
    /**
     * Slugs share one namespace across pages and posts so the public
     * routes /{slug} and /blog/{slug} never collide.
     */
    private const SLUG_TABLES = ['pages', 'posts'];

    private const SLUG_TABLE = 'posts';

    /**
     * @return Collection<int, Post>
     */
    public function all()
    {
        return Post::query()
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    public function byStatus(string $status)
    {
        return Post::query()
            ->where('status', $status)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    public function inCategory(?int $categoryId = null)
    {
        return Post::query()
            ->where('category_id', $categoryId)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    public function search(string $term, ?string $status = null, ?int $categoryId = null, ?string $createdFrom = null, ?string $createdTo = null)
    {
        return Post::query()
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($categoryId !== null, fn ($query) => $query->where('category_id', $categoryId))
            ->when($term !== '', fn ($query) => $query->where('title', 'like', '%'.$term.'%'))
            ->when($createdFrom !== null, fn ($query) => $query->whereDate('created_at', '>=', $createdFrom))
            ->when($createdTo !== null, fn ($query) => $query->whereDate('created_at', '<=', $createdTo))
            ->orderByDesc('id')
            ->get();
    }

    public function find(int $id): ?Post
    {
        return Post::query()->find($id);
    }

    public function findBySlug(string $slug): ?Post
    {
        return Post::query()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Public lookup: only published posts are ever served on /blog/{slug}.
     */
    public function findPublishedBySlug(string $slug): ?Post
    {
        return Post::query()
            ->where('slug', $slug)
            ->where('status', Post::STATUS_PUBLISHED)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Post
    {
        $attributes['status'] ??= Post::STATUS_DRAFT;
        $attributes['slug'] = $this->uniqueSlug(
            (string) ($attributes['slug'] ?? ''),
            (string) ($attributes['title'] ?? ''),
        );

        return Post::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Post $post, array $attributes): Post
    {
        if (array_key_exists('slug', $attributes)) {
            $attributes['slug'] = $this->uniqueSlug(
                (string) ($attributes['slug'] ?? ''),
                (string) ($attributes['title'] ?? $post->title),
                $post->id,
            );
        }

        $post->update($attributes);

        return $post->refresh();
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }

    /**
     * Keep a manually provided slug (suffixing it when already taken) or
     * generate one from the title, always unique across pages and posts.
     */
    private function uniqueSlug(string $slug, string $title, ?int $ignoreId = null): string
    {
        $service = new SlugService;
        $slug = trim($slug);

        return $slug !== ''
            ? $service->uniqueFor($slug, self::SLUG_TABLES, $ignoreId, self::SLUG_TABLE)
            : $service->generateUnique($title, self::SLUG_TABLES, $ignoreId, self::SLUG_TABLE);
    }
}
