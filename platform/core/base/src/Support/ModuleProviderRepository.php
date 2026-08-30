<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ModuleProviderRepository
{
    public function __construct(private readonly string $basePath) {}

    /**
     * @param  array<int, string>  $roots
     * @return array<int, class-string>
     */
    public function providers(array $roots): array
    {
        return array_values(array_unique(array_column($this->providerEntries($roots), 'provider')));
    }

    /**
     * Discover every registrable provider together with the module that owns
     * it and, when the module ships a valid plugin.json manifest, its slug.
     *
     * The manifest is the source of truth for plugin modules: when a
     * directory has a valid plugin.json, its `provider` class replaces the
     * composer.json `extra.laravel.providers` list entirely, so registration
     * can be gated on the plugin's active state (P4-03). Modules without a
     * manifest keep the composer-driven behaviour.
     *
     * @param  array<int, string>  $roots
     * @return array<int, array{provider: class-string, path: string, slug: string|null}>
     */
    public function providerEntries(array $roots): array
    {
        $entries = [];

        foreach ($this->moduleDirectories($roots) as $modulePath) {
            $composer = $this->readJsonFile($modulePath.'/composer.json');
            $manifest = $this->readManifest($modulePath.'/plugin.json');

            if ($manifest !== null) {
                $providers = Arr::wrap($manifest['provider']);
                $autoload = $manifest['autoload'] !== []
                    ? $manifest['autoload']
                    : Arr::get($composer, 'autoload.psr-4', []);
            } else {
                $providers = Arr::get($composer, 'extra.laravel.providers', []);
                $autoload = Arr::get($composer, 'autoload.psr-4', []);
            }

            foreach (Arr::wrap($providers) as $provider) {
                if (! is_string($provider) || $provider === '') {
                    continue;
                }

                $this->loadProviderClass($provider, $autoload, $modulePath);

                if (class_exists($provider)) {
                    $entries[] = [
                        'provider' => $provider,
                        'path' => $modulePath,
                        'slug' => $manifest['slug'] ?? null,
                    ];
                }
            }
        }

        return $entries;
    }

    /**
     * Directories below the roots holding a composer.json or a plugin.json,
     * sorted for a deterministic registration order.
     *
     * @param  array<int, string>  $roots
     * @return array<int, string>
     */
    private function moduleDirectories(array $roots): array
    {
        $modulePaths = [];

        foreach ($roots as $root) {
            if (! is_string($root) || $root === '') {
                continue;
            }

            $rootPath = $this->absolutePath($root);

            if (! is_dir($rootPath)) {
                continue;
            }

            foreach (['composer.json', 'plugin.json'] as $markerFile) {
                foreach (glob($rootPath.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.$markerFile) ?: [] as $marker) {
                    $modulePaths[] = dirname($marker);
                }
            }
        }

        return array_values(array_unique($modulePaths));
    }

    /**
     * Parsed plugin.json manifest using the same validity rule as
     * PluginManager: name, slug and version must be non-empty strings, so a
     * broken manifest degrades the module back to composer behaviour.
     *
     * @return array{name: string, slug: string, provider: string|null, autoload: array<string, string|array<int, string>>}|null
     */
    private function readManifest(string $manifestFile): ?array
    {
        $manifest = $this->readJsonFile($manifestFile);

        if ($manifest === []) {
            return null;
        }

        $name = $manifest['name'] ?? null;
        $slug = $manifest['slug'] ?? null;
        $version = $manifest['version'] ?? null;

        if (! is_string($name) || $name === '' || ! is_string($slug) || $slug === '' || ! is_string($version) || $version === '') {
            return null;
        }

        $provider = $manifest['provider'] ?? null;
        $autoload = $manifest['autoload']['psr-4'] ?? [];

        return [
            'name' => $name,
            'slug' => $slug,
            'provider' => is_string($provider) && $provider !== '' ? $provider : null,
            'autoload' => is_array($autoload) ? $autoload : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonFile(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, string|array<int, string>>  $autoload
     */
    private function loadProviderClass(string $provider, array $autoload, string $modulePath): void
    {
        if (class_exists($provider)) {
            return;
        }

        foreach ($autoload as $namespace => $paths) {
            if (! is_string($namespace) || ! Str::startsWith($provider, $namespace)) {
                continue;
            }

            foreach (Arr::wrap($paths) as $path) {
                if (! is_string($path)) {
                    continue;
                }

                $relativeClass = Str::after($provider, $namespace);
                $providerFile = $modulePath
                    .DIRECTORY_SEPARATOR
                    .trim($path, DIRECTORY_SEPARATOR)
                    .DIRECTORY_SEPARATOR
                    .str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass)
                    .'.php';

                if (is_file($providerFile)) {
                    require_once $providerFile;

                    return;
                }
            }
        }
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return $this->basePath.DIRECTORY_SEPARATOR.$path;
    }
}
