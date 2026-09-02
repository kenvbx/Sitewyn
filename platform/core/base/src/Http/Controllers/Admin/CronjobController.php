<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Sitewyn\Core\Base\Support\SettingStore;

class CronjobController extends Controller
{
    public function __invoke(SettingStore $settings): View
    {
        $command = sprintf(
            '* * * * * %s %s schedule:run >> /dev/null 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(base_path('artisan')),
        );
        $lastRunAt = $this->lastRunAt($settings->get('cronjob_last_run_at'));
        $configured = $lastRunAt?->greaterThan(now()->subMinutes(3)) ?? false;

        return view('core/base::admin.cronjob.index', [
            'command' => $command,
            'configured' => $configured,
            'lastRunAt' => $lastRunAt,
        ]);
    }

    private function lastRunAt(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
