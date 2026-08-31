<?php

namespace Sitewyn\Core\Base\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'is_default', 'is_active'])]
class Language extends Model
{
    /**
     * Active languages that are not the site default — the only locales
     * content may be translated into (default-language content lives on the
     * un-localized URLs). Used by the admin translation forms, the request
     * validation, and the frontend locale routes.
     *
     * @return Collection<int, Language>
     */
    public static function translatable(): Collection
    {
        return static::query()
            ->where('is_active', true)
            ->where('is_default', false)
            ->orderBy('name')
            ->get();
    }

    /**
     * Resolve an active, non-default language by code, or null when the code
     * is unknown, inactive, or the default itself — localized URLs never
     * serve the default language and unknown locales must 404.
     */
    public static function findTranslatable(string $code): ?self
    {
        return static::query()
            ->where('code', mb_strtolower($code))
            ->where('is_active', true)
            ->where('is_default', false)
            ->first();
    }

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
