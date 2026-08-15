<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ModuleProviderRepository
{
    public function __construct(private readonly string $basePath)
    {
    }

    /**
     * @param  array<int, string>  $roots
     * @return array<int, class-string>
     */
    public function providers(array $roots): array
    {
        $providers = [];

        foreach ($this->composerFiles($roots) as $composerFile) {
            $composer = $this->readComposerFile($composerFile);
            $modulePath = dirname($composerFile);

            foreach (Arr::wrap(Arr::get($composer, 'extra.laravel.providers', [])) as $provider) {
                if (! is_string($provider) || $provider === '') {
                    continue;
                }

                $this->loadProviderClass($provider, Arr::get($composer, 'autoload.psr-4', []), $modulePath);

                if (class_exists($provider)) {
                    $providers[] = $provider;
                }
            }
        }

        return array_values(array_unique($providers));
    }

    /**
     * @param  array<int, string>  $roots
     * @return array<int, string>
     */
    private function composerFiles(array $roots): array
    {
        $composerFiles = [];

        foreach ($roots as $root) {
            if (! is_string($root) || $root === '') {
                continue;
            }

            $rootPath = $this->absolutePath($root);

            if (! is_dir($rootPath)) {
                continue;
            }

            foreach (glob($rootPath . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'composer.json') ?: [] as $composerFile) {
                $composerFiles[] = $composerFile;
            }
        }

        sort($composerFiles);

        return $composerFiles;
    }

    /**
     * @return array<string, mixed>
     */
    private function readComposerFile(string $composerFile): array
    {
        $content = file_get_contents($composerFile);

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
                    . DIRECTORY_SEPARATOR
                    . trim($path, DIRECTORY_SEPARATOR)
                    . DIRECTORY_SEPARATOR
                    . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass)
                    . '.php';

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

        return $this->basePath . DIRECTORY_SEPARATOR . $path;
    }
}
