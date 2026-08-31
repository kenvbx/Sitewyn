<?php

namespace Sitewyn\Core\Base\Support;

class SitemapRegistry
{
    /**
     * @var array<int, callable(): array<int, array<string, mixed>>>
     */
    private array $contributors = [];

    /**
     * Register a callable returning sitemap entries. Contributors run lazily,
     * once per /sitemap.xml request, so registering is cheap and content
     * published after registration shows up on the next request with no cache
     * to clear.
     *
     * Each entry is ['loc' => string absolute URL, 'lastmod' => \DateTimeInterface|null].
     */
    public function register(callable $contributor): void
    {
        $this->contributors[] = $contributor;
    }

    /**
     * @return array<int, callable(): array<int, array<string, mixed>>>
     */
    public function contributors(): array
    {
        return $this->contributors;
    }

    /**
     * Invoke every contributor and merge their entries, keyed by loc so the
     * same URL contributed twice is emitted once (first contributor wins).
     *
     * @return array<int, array{loc: string, lastmod: \DateTimeInterface|null}>
     */
    public function entries(): array
    {
        $entries = [];

        foreach ($this->contributors as $contributor) {
            foreach ((array) $contributor() as $entry) {
                $loc = (string) ($entry['loc'] ?? '');

                if ($loc === '') {
                    continue;
                }

                $entries[$loc] ??= [
                    'loc' => $loc,
                    'lastmod' => $entry['lastmod'] instanceof \DateTimeInterface ? $entry['lastmod'] : null,
                ];
            }
        }

        return array_values($entries);
    }
}
