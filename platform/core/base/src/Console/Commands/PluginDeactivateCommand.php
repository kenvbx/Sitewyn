<?php

namespace Sitewyn\Core\Base\Console\Commands;

use Illuminate\Console\Command;
use Sitewyn\Core\Base\Exceptions\PluginDependencyException;
use Sitewyn\Core\Base\Exceptions\PluginMigrationFailedException;
use Sitewyn\Core\Base\Exceptions\PluginNotFoundException;
use Sitewyn\Core\Base\Support\PluginActivator;

class PluginDeactivateCommand extends Command
{
    protected $signature = 'plugin:deactivate
        {slug : The plugin slug to deactivate}
        {--rollback : Also roll back the plugin migrations (plugin data is lost)}';

    protected $description = 'Deactivate a plugin while keeping its data (unless --rollback)';

    public function handle(PluginActivator $activator): int
    {
        $slug = (string) $this->argument('slug');
        $rollback = (bool) $this->option('rollback');

        try {
            $activator->deactivate($slug, $rollback);
        } catch (PluginNotFoundException|PluginDependencyException|PluginMigrationFailedException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info($rollback
            ? "Plugin [{$slug}] deactivated. Its data was removed."
            : "Plugin [{$slug}] deactivated. Its data was kept.");

        return self::SUCCESS;
    }
}
