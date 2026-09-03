<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Sitewyn\Core\Base\Support\SettingStore;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('system:cron-heartbeat', function (SettingStore $settings): int {
    $settings->setMany([
        'cronjob_last_run_at' => now()->toISOString(),
    ], 'system');

    $this->info('Cron heartbeat recorded.');

    return 0;
})->purpose('Record the latest successful scheduler heartbeat.');

Schedule::command('system:cron-heartbeat')->everyMinute();
