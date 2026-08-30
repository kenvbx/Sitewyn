<?php

namespace Sitewyn\Packages\Blog\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'parent_id'])]
class Category extends Model
{
    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Breadth-first walk of the whole subtree below this category, used to
     * keep parent picks from forming a cycle (a category may never live
     * inside its own descendants).
     *
     * @return Collection<int, Category>
     */
    public function descendants(): Collection
    {
        $visited = collect([(int) $this->id]);
        $descendants = Collection::make();
        $frontier = $this->children;

        while ($frontier->isNotEmpty()) {
            $descendants = $descendants->merge($frontier);
            $visited = $visited->merge($frontier->pluck('id'));

            $frontier = $frontier
                ->flatMap(fn (Category $category): Collection => $category->children)
                ->reject(fn (Category $category): bool => $visited->contains((int) $category->id))
                ->values();
        }

        return $descendants;
    }
}
