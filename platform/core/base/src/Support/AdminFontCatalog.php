<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AdminFontCatalog
{
    private const SOURCE_URL = 'https://fonts.google.com/metadata/fonts';

    private const CACHE_KEY = 'sitewyn.admin.google_fonts.v2';

    /**
     * @return Collection<int, array{key: string, family: string, category: string|null, subsets: array<int, string>, popularity: int|null, last_modified: string|null}>
     */
    public function fonts(): Collection
    {
        $fonts = Cache::remember(self::CACHE_KEY, now()->addDay(), function (): array {
            $fonts = $this->fetchFromGoogle();

            return ($fonts->isNotEmpty() ? $fonts : $this->fallbackFonts())->all();
        });

        return collect(is_array($fonts) ? $fonts : []);
    }

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        return $this->fonts()
            ->mapWithKeys(fn (array $font): array => [
                $font['key'] => $font['family'].($font['category'] ? ' - '.$font['category'] : ''),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->options());
    }

    /**
     * @return array{key: string, family: string, category: string|null, subsets: array<int, string>, popularity: int|null, last_modified: string|null}|null
     */
    public function find(string $key): ?array
    {
        $font = $this->fonts()->firstWhere('key', $key);

        return is_array($font) ? $font : null;
    }

    public function family(string $key): string
    {
        return $this->find($key)['family'] ?? 'Inter';
    }

    public function stylesheetUrl(string $key): string
    {
        return $this->stylesheetUrlForFamily($this->family($key));
    }

    public function stylesheetUrlForFamily(string $family): string
    {
        return 'https://fonts.googleapis.com/css2?family='.
            str_replace('%20', '+', rawurlencode($family)).
            ':wght@400;500;600;700&display=swap';
    }

    /**
     * @return array{results: array<int, array{id: string, text: string, family: string, category: string|null, stylesheet_url: string}>, pagination: array{more: bool}}
     */
    public function search(?string $term = null, int $page = 1, int $perPage = 20): array
    {
        $query = Str::lower(trim((string) $term));
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));

        $fonts = $this->fonts()
            ->when($query !== '', fn (Collection $fonts): Collection => $fonts->filter(
                fn (array $font): bool => str_contains(Str::lower($font['family']), $query)
                    || str_contains(Str::lower((string) $font['category']), $query)
            ))
            ->values();

        return [
            'results' => $fonts
                ->forPage($page, $perPage)
                ->map(fn (array $font): array => [
                    'id' => $font['key'],
                    'text' => $font['family'],
                    'family' => $font['family'],
                    'category' => $font['category'],
                    'stylesheet_url' => $this->stylesheetUrlForFamily($font['family']),
                ])
                ->values()
                ->all(),
            'pagination' => [
                'more' => $fonts->count() > ($page * $perPage),
            ],
        ];
    }

    /**
     * @return Collection<int, array{key: string, family: string, category: string|null, subsets: array<int, string>, popularity: int|null, last_modified: string|null}>
     */
    private function fetchFromGoogle(): Collection
    {
        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get(self::SOURCE_URL);
        } catch (\Throwable) {
            return collect();
        }

        if (! $response->successful()) {
            return collect();
        }

        $body = preg_replace("/^\\)\\]\\}'\\n/", '', $response->body()) ?: '';
        $payload = json_decode($body, true);
        $fonts = is_array($payload) && is_array($payload['familyMetadataList'] ?? null)
            ? $payload['familyMetadataList']
            : [];

        return $this->normalizeGoogleFonts($fonts);
    }

    /**
     * @return Collection<int, array{key: string, family: string, category: string|null, subsets: array<int, string>, popularity: int|null, last_modified: string|null}>
     */
    private function fallbackFonts(): Collection
    {
        $path = dirname(__DIR__, 2).'/resources/data/google-fonts.json';
        $payload = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $fonts = is_array($payload) && is_array($payload['fonts'] ?? null) ? $payload['fonts'] : [];

        return collect($fonts)
            ->filter(fn (mixed $font): bool => is_array($font) && is_string($font['key'] ?? null) && is_string($font['family'] ?? null))
            ->values();
    }

    /**
     * @param  array<int, mixed>  $fonts
     * @return Collection<int, array{key: string, family: string, category: string|null, subsets: array<int, string>, popularity: int|null, last_modified: string|null}>
     */
    private function normalizeGoogleFonts(array $fonts): Collection
    {
        $keyCounts = [];

        return collect($fonts)
            ->filter(fn (mixed $font): bool => is_array($font) && is_string($font['family'] ?? null) && trim($font['family']) !== '')
            ->map(function (array $font) use (&$keyCounts): array {
                $family = trim($font['family']);
                $baseKey = Str::of($family)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
                $keyCounts[$baseKey] = ($keyCounts[$baseKey] ?? 0) + 1;

                return [
                    'key' => $keyCounts[$baseKey] === 1 ? $baseKey : $baseKey.'_'.$keyCounts[$baseKey],
                    'family' => $family,
                    'category' => Str::of((string) ($font['category'] ?? ''))->replace('_', ' ')->title()->toString(),
                    'subsets' => is_array($font['subsets'] ?? null) ? array_values($font['subsets']) : [],
                    'popularity' => is_int($font['popularity'] ?? null) ? $font['popularity'] : null,
                    'last_modified' => is_string($font['lastModified'] ?? null) ? $font['lastModified'] : null,
                ];
            })
            ->sortBy([
                fn (array $a, array $b): int => ($a['popularity'] ?? PHP_INT_MAX) <=> ($b['popularity'] ?? PHP_INT_MAX),
                fn (array $a, array $b): int => strcasecmp($a['family'], $b['family']),
            ])
            ->values();
    }
}
