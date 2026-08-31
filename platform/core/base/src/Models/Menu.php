<?php

namespace Sitewyn\Core\Base\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'location'])]
class Menu extends Model
{
    /**
     * The only themed location of the MVP (P5-03): the default theme's
     * header nav. Custom themes may honour more locations, but the admin
     * builder only offers this one.
     */
    public const LOCATION_PRIMARY = 'primary';

    /**
     * Menu items in save order, children attached one level deep.
     *
     * @return HasMany<MenuItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->defaultOrder();
    }

    /**
     * Top-level items with their children eager-loaded and ordered — the
     * single query shape the frontend nav renderer needs.
     */
    public static function forLocation(string $location): ?self
    {
        return static::query()
            ->where('location', $location)
            ->with('items.children')
            ->first();
    }
}
