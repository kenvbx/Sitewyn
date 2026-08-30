<?php

namespace Sitewyn\Packages\Blog\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Sitewyn\Packages\Blog\Models\Category;

class CategoryRepository
{
    /**
     * @return Collection<int, Category>
     */
    public function all()
    {
        return Category::query()
            ->withCount('posts')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Category>
     */
    public function childrenOf(?int $parentId = null)
    {
        return Category::query()
            ->where('parent_id', $parentId)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Category>
     */
    public function searchByName(string $term, ?int $parentId = null)
    {
        return Category::query()
            ->withCount('posts')
            ->when($parentId !== null, fn ($query) => $query->where('parent_id', $parentId))
            ->where('name', 'like', '%'.$term.'%')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?Category
    {
        return Category::query()->find($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        return Category::query()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Category
    {
        return Category::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Category $category, array $attributes): Category
    {
        $category->update($attributes);

        return $category->refresh();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}
