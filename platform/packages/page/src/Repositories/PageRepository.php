<?php

namespace Sitewyn\Packages\Page\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Sitewyn\Core\Base\Support\SlugService;
use Sitewyn\Packages\Page\Models\Page;

class PageRepository
{
    /**
     * Slugs share one namespace across pages and posts so the public
     * routes /{slug} and /blog/{slug} never collide.
     */
    private const SLUG_TABLES = ['pages', 'posts'];

    private const SLUG_TABLE = 'pages';

    /**
     * @return Collection<int, Page>
     */
    public function all()
    {
        return Page::query()
            ->orderBy('title')
            ->get();
    }

    /**
     * @return Collection<int, Page>
     */
    public function byStatus(string $status)
    {
        return Page::query()
            ->where('status', $status)
            ->orderBy('title')
            ->get();
    }

    /**
     * @return Collection<int, Page>
     */
    public function search(string $term, ?string $status = null, ?string $createdFrom = null, ?string $createdTo = null)
    {
        return Page::query()
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($term !== '', fn ($query) => $query->where('title', 'like', '%'.$term.'%'))
            ->when($createdFrom !== null, fn ($query) => $query->whereDate('created_at', '>=', $createdFrom))
            ->when($createdTo !== null, fn ($query) => $query->whereDate('created_at', '<=', $createdTo))
            ->orderBy('title')
            ->get();
    }

    public function find(int $id): ?Page
    {
        return Page::query()->find($id);
    }

    public function findBySlug(string $slug): ?Page
    {
        return Page::query()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Public lookup: only published pages are ever served on /{slug}.
     */
    public function findPublishedBySlug(string $slug): ?Page
    {
        return Page::query()
            ->where('slug', $slug)
            ->where('status', Page::STATUS_PUBLISHED)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Page
    {
        $attributes['status'] ??= Page::STATUS_DRAFT;
        $attributes['slug'] = $this->uniqueSlug(
            (string) ($attributes['slug'] ?? ''),
            (string) ($attributes['title'] ?? ''),
        );

        return Page::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Page $page, array $attributes): Page
    {
        if (array_key_exists('slug', $attributes)) {
            $attributes['slug'] = $this->uniqueSlug(
                (string) ($attributes['slug'] ?? ''),
                (string) ($attributes['title'] ?? $page->title),
                $page->id,
            );
        }

        $page->update($attributes);

        return $page->refresh();
    }

    public function delete(Page $page): void
    {
        $page->delete();
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
