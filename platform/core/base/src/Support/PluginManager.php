<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PluginManager
{
    private const PLUGIN_ROOT = 'platform/plugins';

    private const PACKAGE_ROOT = 'platform/packages';

    /**
     * Relative-to-plugin default where scoped migrations live (P4-05).
     */
    public const DEFAULT_MIGRATIONS_PATH = 'database/migrations';

    /**
     * Scan order matters: a slug present in both sources is owned by
     * platform/plugins.
     */
    private const SOURCES = [
        self::PLUGIN_ROOT => 'plugin',
        self::PACKAGE_ROOT => 'package',
    ];

    private ?Collection $pluginsCache = null;

    private ?array $deactivatedCache = null;

    private readonly string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
    }

    /**
     * Drop the cached scan + deactivated-slugs state. Commands that mutate
     * plugin rows call this so lookups in the same process stay truthful.
     */
    public function refresh(): void
    {
        $this->pluginsCache = null;
        $this->deactivatedCache = null;
    }

    /**
     * Every discoverable plugin (valid plugin.json) merged with its active
     * state, sorted by slug.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function all(): Collection
    {
        return $this->pluginsCache ??= $this->scan();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $slug): ?array
    {
        return $this->all()->firstWhere('slug', $slug);
    }

    /**
     * A plugin is active when it is discoverable and not explicitly
     * deactivated. A missing plugins-table row counts as active.
     */
    public function isActive(string $slug): bool
    {
        return in_array($slug, $this->activeSlugs(), true);
    }

    /**
     * Slugs safe to boot: available plugins minus deactivated ones.
     *
     * @return array<int, string>
     */
    public function activeSlugs(): array
    {
        return $this->all()
            ->reject(fn (array $plugin): bool => $plugin['isActive'] === false)
            ->pluck('slug')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function availableSlugs(): array
    {
        return $this->all()->pluck('slug')->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function scan(): Collection
    {
        $deactivated = $this->deactivatedSlugs();
        $plugins = [];

        foreach (self::SOURCES as $root => $source) {
            foreach ($this->manifestFiles($root) as $manifestFile) {
                $plugin = $this->readManifest($manifestFile, $source, $deactivated);

                if ($plugin === null) {
                    continue;
                }

                $plugins[$plugin['slug']] ??= $plugin;
            }
        }

        ksort($plugins);

        return collect(array_values($plugins));
    }

    /**
     * @return array<int, string>
     */
    private function manifestFiles(string $root): array
    {
        $rootPath = $this->basePath.DIRECTORY_SEPARATOR.$root;

        if (! is_dir($rootPath)) {
            return [];
        }

        $manifestFiles = glob($rootPath.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'plugin.json') ?: [];
        sort($manifestFiles);

        return $manifestFiles;
    }

    /**
     * @param  array<int, string>  $deactivated
     * @return array<string, mixed>|null
     */
    private function readManifest(string $manifestFile, string $source, array $deactivated): ?array
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
        $provider = $manifest['provider'] ?? null;
        $requirements = $this->normalizeRequirements($manifest['requires'] ?? []);
        $migrations = $manifest['migrations'] ?? null;

        return [
            'name' => $name,
            'slug' => $slug,
            'version' => $version,
            'description' => is_string($description) && $description !== '' ? $description : null,
            'provider' => is_string($provider) && $provider !== '' ? $provider : null,
            'requires' => array_keys($requirements),
            'constraints' => array_filter($requirements, fn (?string $constraint): bool => $constraint !== null),
            'migrations' => is_string($migrations) && $migrations !== '' ? $migrations : self::DEFAULT_MIGRATIONS_PATH,
            'path' => dirname($manifestFile),
            'isActive' => ! in_array($slug, $deactivated, true),
            'source' => $source,
        ];
    }

    /**
     * Slugs explicitly deactivated through the plugins table. A missing table
     * (e.g. the first artisan migrate run) counts as all-active.
     *
     * Read through the database manager (DB::table) instead of Eloquent: the
     * Eloquent connection resolver is only wired during service provider
     * boot(), while this lookup already runs inside provider registration.
     *
     * @return array<int, string>
     */
    private function deactivatedSlugs(): array
    {
        if ($this->deactivatedCache !== null) {
            return $this->deactivatedCache;
        }

        try {
            $this->deactivatedCache = DB::table('plugins')
                ->where('is_active', false)
                ->pluck('slug')
                ->all();
        } catch (QueryException) {
            $this->deactivatedCache = [];
        }

        return $this->deactivatedCache;
    }

    /**
     * "requires" accepts two shapes (P4-09): the P4-07 plain slug list and a
     * slug => version-constraint map. Both normalize to an ordered
     * slug => constraint map with null for unconstrained slugs, so
     * `requires` stays a plain slug list for the dependency rules and
     * `constraints` carries only the slugs that declared one.
     *
     * @return array<string, string|null>
     */
    private function normalizeRequirements(mixed $requires): array
    {
        if (! is_array($requires)) {
            return [];
        }

        $requirements = [];

        foreach ($requires as $slug => $constraint) {
            if (is_int($slug) && is_string($constraint) && $constraint !== '') {
                $requirements[$constraint] = null;

                continue;
            }

            if (is_string($slug) && $slug !== '' && is_string($constraint) && $constraint !== '') {
                $requirements[$slug] = $constraint;
            }
        }

        return $requirements;
    }
}
