<?php

namespace Sitewyn\Core\Base\Console\Commands;

use Illuminate\Console\Command;
use Sitewyn\Core\Base\Support\PluginManager;

class PluginListCommand extends Command
{
    protected $signature = 'plugin:list';

    protected $description = 'List discovered plugins and their active state';

    public function handle(PluginManager $manager): int
    {
        $plugins = $manager->all();

        if ($plugins->isEmpty()) {
            $this->components->info('No plugins discovered.');

            return self::SUCCESS;
        }

        $this->table(
            ['Slug', 'Name', 'Version', 'Source', 'Active'],
            $plugins->map(fn (array $plugin): array => [
                $plugin['slug'],
                $plugin['name'],
                $plugin['version'],
                $plugin['source'],
                $plugin['isActive'] ? '✓' : '✗',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
