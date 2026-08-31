<?php

namespace Sitewyn\Core\Base\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['area_slug', 'type', 'data', 'order'])]
class Widget extends Model
{
    public const TYPE_PAGES = 'pages';

    public const TYPE_RECENT_POSTS = 'recent-posts';

    public const TYPE_TEXT = 'text';

    public const TYPES = [self::TYPE_PAGES, self::TYPE_RECENT_POSTS, self::TYPE_TEXT];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * Widgets of one area in save order; id as the tiebreaker keeps repeat
     * renders stable for rows stored with the same order value.
     */
    public function scopeInArea(Builder $query, string $areaSlug): Builder
    {
        return $query->where('area_slug', $areaSlug)->orderBy('order')->orderBy('id');
    }
}
