<?php

namespace Sitewyn\Packages\Page\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'slug', 'content', 'seo_title', 'seo_description', 'og_image', 'status'])]
class Page extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    /**
     * Content translations for the active non-default languages (P5-01).
     * Slugs are shared from the default language on purpose — a translation
     * never owns a slug, so the SlugService namespace stays untouched and
     * translations live at /{locale}/{default-slug}.
     *
     * @return HasMany<PageTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }
}
