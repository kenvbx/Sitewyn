<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Support\Collection;

class ThemeManager
{
    private const THEME_ROOT = 'platform/themes';

    /**
     * Used when the active_theme setting is missing or points at a theme that
     * is not discoverable — the frontend always renders a real theme.
     */
    public const DEFAULT_THEME = 'default';

    private ?Collection $themesCache = null;

    private readonly string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
    }

    /**
     * Every discoverable theme (valid theme.json), sorted by slug.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function all(): Collection
    {
        return $this->themesCache ??= $this->scan();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $slug): ?array
    {
        return $this->all()->firstWhere('slug', $slug);
    }

    /**
     * @return array<int, string>
     */
    public function availableSlugs(): array
    {
        return $this->all()->pluck('slug')->all();
    }

    /**
     * The theme that renders the frontend: the active_theme setting when it
     * points at a discoverable theme, otherwise the default theme. An empty
     * manifest means even the default is missing — callers skip gracefully
     * instead of crashing (the frontend then simply has no theme views).
     *
     * @return array<string, mixed>
     */
    public function activeTheme(): array
    {
        $setting = app(SettingStore::class)->get('active_theme', self::DEFAULT_THEME);
        $slug = is_string($setting) && $setting !== '' ? $setting : self::DEFAULT_THEME;

        return $this->find($slug) ?? $this->find(self::DEFAULT_THEME) ?? [];
    }

    /**
     * Widget areas the active theme declares (P5-04). MVP: only the active
     * theme's declarations are exposed — switching themes moves the admin
     * and the frontend to the new areas in one step. Themes without a
     * widget_areas key simply have none.
     *
     * @return array<int, array{slug: string, name: string}>
     */
    public function widgetAreas(): array
    {
        return $this->activeTheme()['widget_areas'] ?? [];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function scan(): Collection
    {
        $themes = [];

        foreach ($this->manifestFiles() as $manifestFile) {
            $theme = $this->readManifest($manifestFile);

            if ($theme === null) {
                continue;
            }

            $themes[$theme['slug']] = $theme;
        }

        ksort($themes);

        return collect(array_values($themes));
    }

    /**
     * @return array<int, string>
     */
    private function manifestFiles(): array
    {
        $rootPath = $this->basePath.DIRECTORY_SEPARATOR.self::THEME_ROOT;

        if (! is_dir($rootPath)) {
            return [];
        }

        $manifestFiles = glob($rootPath.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'theme.json') ?: [];
        sort($manifestFiles);

        return $manifestFiles;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readManifest(string $manifestFile): ?array
    {
        $content = file_get_contents($manifestFile);

        if ($content === false) {
            return null;
        }

        $manifest = json_decode($content, true);

        if (! is_array($manifest)) {
            return null;
        }

        $name = $manifest['name'] ?? null;
        $slug = $manifest['slug'] ?? null;
        $version = $manifest['version'] ?? null;

        if (! is_string($name) || $name === '' || ! is_string($slug) || $slug === '' || ! is_string($version) || $version === '') {
            return null;
        }

        $description = $manifest['description'] ?? null;
        $author = $manifest['author'] ?? null;

        return [
            'name' => $name,
            'slug' => $slug,
            'version' => $version,
            'description' => is_string($description) && $description !== '' ? $description : null,
            'author' => is_string($author) && $author !== '' ? $author : null,
            'widget_areas' => $this->widgetAreasFromManifest($manifest),
            'path' => dirname($manifestFile),
        ];
    }

    /**
     * Widget areas (P5-04) declared by the manifest:
     * "widget_areas": [{"slug": "footer", "name": "Footer widgets"}].
     * Every entry needs a slug (kebab-style, matching the DB column shape)
     * and a name; malformed or duplicate entries are dropped silently so a
     * broken declaration never breaks the admin or the frontend.
     *
     * @return array<int, array{slug: string, name: string}>
     */
    private function widgetAreasFromManifest(array $manifest): array
    {
        $declared = $manifest['widget_areas'] ?? [];

        if (! is_array($declared)) {
            return [];
        }

        $areas = [];
        $seen = [];

        foreach ($declared as $area) {
            if (! is_array($area)) {
                continue;
            }

            $slug = $area['slug'] ?? null;
            $name = $area['name'] ?? null;

            if (
                ! is_string($slug) || $slug === ''
                || ! is_string($name) || $name === ''
                || preg_match('/^[a-z0-9_-]+$/', $slug) !== 1
                || isset($seen[$slug])
            ) {
                continue;
            }

            $seen[$slug] = true;
            $areas[] = ['slug' => $slug, 'name' => $name];
        }

        return $areas;
    }
}
