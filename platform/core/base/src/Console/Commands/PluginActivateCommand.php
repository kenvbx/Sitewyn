<?php

namespace Sitewyn\Core\Base\Console\Commands;

use Illuminate\Console\Command;
use Sitewyn\Core\Base\Exceptions\PluginDependencyException;
use Sitewyn\Core\Base\Exceptions\PluginMigrationFailedException;
use Sitewyn\Core\Base\Exceptions\PluginNotFoundException;
use Sitewyn\Core\Base\Support\PluginActivator;

class PluginActivateCommand extends Command
{
    protected $signature = 'plugin:activate
        {slug : The plugin slug to activate}';

    protected $description = 'Activate a plugin, running its migrations on first activation';

    public function handle(PluginActivator $activator): int
    {
        $slug = (string) $this->argument('slug');

        try {
            $activator->activate($slug);
        } catch (PluginNotFoundException|PluginDependencyException|PluginMigrationFailedException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Plugin [{$slug}] activated.");

        return self::SUCCESS;
    }
}
