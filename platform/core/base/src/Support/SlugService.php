<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SlugService
{
    private const FALLBACK_SLUG = 'untitled';

    private const MAX_ATTEMPTS = 100;

    /**
     * Turn a source string into a URL-friendly slug.
     *
     * Sources that produce an empty slug (empty, whitespace, or
     * punctuation-only) fall back to "untitled" so records always
     * receive a usable slug.
     */
    public function generate(string $source): string
    {
        $slug = Str::slug($source);

        return $slug !== '' ? $slug : self::FALLBACK_SLUG;
    }

    /**
     * Suffix a slug with -2, -3, ... until it is unique across the given
     * tables (each checked on their "slug" column).
     *
     * When updating an existing record, pass its id as $ignoreId so its
     * own row does not force a suffix. In multi-table setups also pass
     * $ignoreTable (the table owning the record) — otherwise rows in the
     * other tables that happen to share the same id would be skipped too.
     *
     * @param  array<int, string>  $tables
     *
     * @throws RuntimeException when no unique slug is found after 100 attempts
     */
    public function uniqueFor(string $slug, array $tables, ?int $ignoreId = null, ?string $ignoreTable = null): string
    {
        $slug = trim($slug);

        if ($slug === '') {
            return '';
        }

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $candidate = $attempt === 0 ? $slug : $slug.'-'.($attempt + 1);

            if (! $this->exists($candidate, $tables, $ignoreId, $ignoreTable)) {
                return $candidate;
            }
        }

        throw new RuntimeException(sprintf('Unable to find a unique slug for [%s] after %d attempts.', $slug, self::MAX_ATTEMPTS));
    }

    /**
     * Generate a slug from the source and make it unique in one call.
     *
     * @param  array<int, string>  $tables
     */
    public function generateUnique(string $source, array $tables, ?int $ignoreId = null, ?string $ignoreTable = null): string
    {
        return $this->uniqueFor($this->generate($source), $tables, $ignoreId, $ignoreTable);
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function exists(string $slug, array $tables, ?int $ignoreId, ?string $ignoreTable): bool
    {
        foreach ($tables as $table) {
            $query = DB::table($table)->where('slug', $slug);

            if ($ignoreId !== null && ($ignoreTable === null || $ignoreTable === $table)) {
                $query->where('id', '!=', $ignoreId);
            }

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }
}
