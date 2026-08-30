<?php

namespace Sitewyn\Packages\Blog\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Sitewyn\Packages\Blog\Models\Tag;

class TagRepository
{
    /**
     * @return Collection<int, Tag>
     */
    public function all()
    {
        return Tag::query()
            ->withCount('posts')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Tag>
     */
    public function searchByName(string $term)
    {
        return Tag::query()
            ->withCount('posts')
            ->where('name', 'like', '%'.$term.'%')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?Tag
    {
        return Tag::query()->find($id);
    }

    public function findByName(string $name): ?Tag
    {
        return Tag::query()
            ->where('name', $name)
            ->first();
    }

    public function findBySlug(string $slug): ?Tag
    {
        return Tag::query()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Tag
    {
        return Tag::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Tag $tag, array $attributes): Tag
    {
        $tag->update($attributes);

        return $tag->refresh();
    }

    public function delete(Tag $tag): void
    {
        $tag->delete();
    }
}
