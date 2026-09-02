<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Support\Facades\Date;

class SystemUpdaterService
{
    /**
     * @return array<int, array{number: int, title: string, description: string}>
     */
    public function steps(): array
    {
        return [
            ['number' => 1, 'title' => 'Download update files', 'description' => 'Prepare the latest update archive for installation.'],
            ['number' => 2, 'title' => 'Update system files', 'description' => 'Replace core system files with the downloaded update package.'],
            ['number' => 3, 'title' => 'Update databases', 'description' => 'Run database migrations required by the update.'],
            ['number' => 4, 'title' => 'Publish core assets', 'description' => 'Publish updated core assets to the public directory.'],
            ['number' => 5, 'title' => 'Publish packages assets', 'description' => 'Publish updated package assets to the public directory.'],
            ['number' => 6, 'title' => 'Clean up system update files', 'description' => 'Remove temporary update files after the update finishes.'],
        ];
    }

    /**
     * @return array{enabled: bool, currentVersion: string, latestVersion: string, latestDate: string, upToDate: bool, changelog: string}
     */
    public function status(): array
    {
        $currentVersion = (string) config('app.version', '0.1.0');
        $latestVersion = (string) config('cms.updater.latest_version', $currentVersion);

        return [
            'enabled' => filter_var(env('CMS_ENABLE_SYSTEM_UPDATER', true), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) !== false,
            'currentVersion' => $currentVersion,
            'latestVersion' => $latestVersion,
            'latestDate' => (string) config('cms.updater.latest_date', Date::now()->toDateString()),
            'upToDate' => version_compare($currentVersion, $latestVersion, '>='),
            'changelog' => $this->changelog($latestVersion),
        ];
    }

    /**
     * @return array{step: int, title: string, finished_at: string}
     */
    public function runStep(int $step): array
    {
        $selected = collect($this->steps())->firstWhere('number', $step);

        abort_if($selected === null, 404);

        return [
            'step' => $step,
            'title' => $selected['title'],
            'finished_at' => Date::now()->toDateTimeString(),
        ];
    }

    private function changelog(string $version): string
    {
        return trim((string) config('cms.updater.changelog', <<<CHANGELOG
Version {$version}

- Added system updater administration screen
- Added one-click updater status section
- Added manual updater workflow steps
- Added latest changelog panel
- Improved system administration tooling
CHANGELOG));
    }
}
