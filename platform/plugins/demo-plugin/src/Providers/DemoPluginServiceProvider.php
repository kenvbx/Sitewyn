<?php

namespace Sitewyn\Plugins\DemoPlugin\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DemoPluginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::get('/demo-plugin/health', fn (): array => [
            'plugin' => 'demo-plugin',
            'status' => 'ok',
        ])->name('demo-plugin.health');
    }
}
