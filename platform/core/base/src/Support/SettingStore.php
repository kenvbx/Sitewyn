<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Sitewyn\Core\Base\Models\Setting;

class SettingStore
{
    private const CACHE_KEY = 'sitewyn.settings';

    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->load());
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => $group,
                ],
            );
        }

        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function applyApplicationConfig(): void
    {
        $siteName = $this->get('site_name');

        if ($siteName) {
            config(['app.name' => $siteName]);
        }
    }

    /**
     * @return array<string, string|null>
     */
    private function load(): array
    {
        try {
            return Setting::query()
                ->pluck('value', 'key')
                ->all();
        } catch (QueryException) {
            return [];
        }
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (QueryException) {
            return false;
        }
    }
}
