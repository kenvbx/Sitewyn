<?php

namespace Sitewyn\Core\Base\Support;

use Closure;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Sitewyn\Core\Base\Models\Language;

/**
 * Persists per-locale content translations submitted from the admin forms as
 * `translations[<locale>][<field>]` (P5-01).
 *
 * Exactly one row per (parent, locale): a locale whose fields are all empty
 * has its row deleted outright instead of lingering as blank data, so every
 * missing field falls back to the default language. Locale keys are checked
 * against the active, non-default languages even though the FormRequest
 * validation already enforced them — this stays safe for non-HTTP callers.
 * The field list is passed by the caller so this stays decoupled from the
 * page/post/category column shapes.
 */
class Translations
{
    /**
     * Validation closure for the `translations` payload of the admin content
     * forms: every locale key must be an active, non-default language (the
     * same translatable set the forms render), so forged or stale keys —
     * including the default locale — are rejected with 422.
     */
    public static function localeKeyRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null) {
                return;
            }

            $allowed = Language::translatable()->pluck('code')->all();

            foreach (array_keys(is_array($value) ? $value : []) as $locale) {
                if (! in_array((string) $locale, $allowed, true)) {
                    $fail("The {$locale} locale is not available for translation.");
                }
            }
        };
    }

    /**
     * @param  HasMany<*, *>  $relation
     * @param  array<string, mixed>|null  $input
     * @param  array<int, string>  $fields
     */
    public static function save(HasMany $relation, ?array $input, array $fields): void
    {
        $allowedLocales = Language::translatable()->pluck('code')->all();

        foreach ($input ?? [] as $locale => $values) {
            if (! is_string($locale) || ! in_array($locale, $allowedLocales, true) || ! is_array($values)) {
                continue;
            }

            $attributes = [];

            foreach ($fields as $field) {
                $value = $values[$field] ?? null;
                $attributes[$field] = is_string($value) && trim($value) !== '' ? trim($value) : null;
            }

            $hasContent = array_filter($attributes, static fn ($value): bool => $value !== null) !== [];

            if (! $hasContent) {
                $relation->where('locale', $locale)->delete();

                continue;
            }

            $relation->updateOrCreate(['locale' => $locale], $attributes);
        }
    }
}
