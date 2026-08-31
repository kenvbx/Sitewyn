<?php

namespace Sitewyn\Packages\Blog\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'slug', 'content', 'seo_title', 'seo_description', 'og_image', 'status', 'category_id', 'featured_image'])]
class Post extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Content translations for the active non-default languages (P5-01).
     * Slugs are shared from the default language on purpose — a translation
     * never owns a slug, so the SlugService namespace stays untouched and
     * translations live at /{locale}/blog/{default-slug}.
     *
     * @return HasMany<PostTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(PostTranslation::class);
    }
}
