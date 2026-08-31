<?php

namespace Sitewyn\Core\Base\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['menu_id', 'parent_id', 'label', 'type', 'target_id', 'url', 'order'])]
class MenuItem extends Model
{
    public const TYPE_PAGE = 'page';

    public const TYPE_POST = 'post';

    public const TYPE_CUSTOM = 'custom';

    public const TYPES = [self::TYPE_PAGE, self::TYPE_POST, self::TYPE_CUSTOM];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Children of a nested item — only ever one level deep (P5-03 MVP).
     *
     * @return HasMany<MenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->defaultOrder();
    }

    /**
     * Save order within a menu; id as the tiebreaker keeps repeat renders
     * stable for rows saved with the same order value.
     */
    public function scopeDefaultOrder(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('id');
    }
}
