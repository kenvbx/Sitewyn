<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class CacheManagementService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function cacheRows(): array
    {
        return [
            [
                'operation' => 'clear-cms',
                'icon' => 'database',
                'tone' => 'primary',
                'title' => 'Clear all CMS cache',
                'description' => "Clear CMS caching: database caching, static blocks... Run this command when you don't see the changes after updating data.",
                'meta' => 'Current Size: '.$this->formatBytes($this->cmsCacheSize()),
                'button' => 'Clear',
                'buttonIcon' => 'trash',
                'buttonTone' => 'primary',
            ],
            [
                'operation' => 'refresh-views',
                'icon' => 'file-code',
                'tone' => 'warning',
                'title' => 'Refresh compiled views',
                'description' => 'Clear compiled views to make views up to date.',
                'button' => 'Refresh',
                'buttonIcon' => 'reload',
                'buttonTone' => 'warning',
            ],
            [
                'operation' => 'clear-config',
                'icon' => 'settings',
                'tone' => 'azure',
                'title' => 'Clear config cache',
                'description' => 'You might need to refresh the config caching when you change something on production environment.',
                'button' => 'Clear',
                'buttonIcon' => 'reload',
                'buttonTone' => 'azure',
            ],
            [
                'operation' => 'clear-routes',
                'icon' => 'route',
                'tone' => 'success',
                'title' => 'Clear route cache',
                'description' => 'Clear cache routing.',
                'button' => 'Clear',
                'buttonIcon' => 'reload',
                'buttonTone' => 'success',
            ],
            [
                'operation' => 'clear-logs',
                'icon' => 'file-text',
                'tone' => 'danger',
                'title' => 'Clear log',
                'description' => 'Clear system log files',
                'button' => 'Clear',
                'buttonIcon' => 'trash',
                'buttonTone' => 'danger',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function optimizationRows(): array
    {
        return [
            [
                'operation' => 'optimize',
                'icon' => 'bolt',
                'tone' => 'success',
                'title' => 'Optimize site performance',
                'description' => 'Cache configuration, routes, and views for faster loading speed.',
                'button' => 'Optimize',
                'buttonIcon' => 'rocket',
                'buttonTone' => 'success',
            ],
            [
                'operation' => 'clear-optimization',
                'icon' => 'eraser',
                'tone' => 'warning',
                'title' => 'Clear optimization cache',
                'description' => 'Remove optimization caches to allow configuration changes.',
                'button' => 'Clear',
                'buttonIcon' => 'eraser',
                'buttonTone' => 'warning',
            ],
        ];
    }

    public function run(string $operation): void
    {
        match ($operation) {
            'clear-cms' => $this->clearCmsCache(),
            'refresh-views' => Artisan::call('view:clear'),
            'clear-config' => Artisan::call('config:clear'),
            'clear-routes' => Artisan::call('route:clear'),
            'clear-logs' => $this->clearLogs(),
            'optimize' => Artisan::call('optimize'),
            'clear-optimization' => Artisan::call('optimize:clear'),
        };
    }

    public function operationTitle(string $operation): string
    {
        return collect($this->cacheRows())
            ->merge($this->optimizationRows())
            ->firstWhere('operation', $operation)['title'] ?? 'Cache operation';
    }

    private function clearCmsCache(): void
    {
        Cache::flush();
    }

    private function clearLogs(): void
    {
        foreach (File::glob(storage_path('logs/*.log')) ?: [] as $path) {
            File::put($path, '');
        }
    }

    private function cmsCacheSize(): int
    {
        return $this->directorySize(storage_path('framework/cache'));
    }

    private function directorySize(string $path): int
    {
        if (! File::isDirectory($path)) {
            return 0;
        }

        $size = 0;

        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
