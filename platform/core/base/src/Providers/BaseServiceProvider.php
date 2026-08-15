<?php

namespace Sitewyn\Core\Base\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Sitewyn\Core\Base\Support\ModuleProviderRepository;

class BaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/sitewyn-base.php'), 'sitewyn-base');
        $this->registerModuleProviders();
    }

    public function boot(): void
    {
        $this->loadViewsFrom($this->modulePath('resources/views'), 'core/base');
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->registerPasswordResetUrl();
    }

    private function modulePath(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2);

        return $path === '' ? $basePath : $basePath.DIRECTORY_SEPARATOR.$path;
    }

    private function registerModuleProviders(): void
    {
        $repository = new ModuleProviderRepository(base_path());
        $excludedProviders = config('sitewyn-base.modules.excluded_providers', []);

        foreach ($repository->providers(config('sitewyn-base.modules.provider_roots', [])) as $provider) {
            if (in_array($provider, $excludedProviders, true)) {
                continue;
            }

            if (isset($this->app->getLoadedProviders()[$provider])) {
                continue;
            }

            $this->app->register($provider);
        }
    }

    private function registerPasswordResetUrl(): void
    {
        ResetPassword::createUrlUsing(fn (object $notifiable, string $token): string => route('admin.password.reset', [
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]));
    }
}
