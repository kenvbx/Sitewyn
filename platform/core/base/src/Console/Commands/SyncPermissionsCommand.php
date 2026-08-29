<?php

namespace Sitewyn\Core\Base\Console\Commands;

use Illuminate\Console\Command;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Support\PermissionRegistry;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'permission:sync';

    protected $description = 'Sync registered admin permissions to the database';

    public function handle(PermissionRegistry $registry): int
    {
        $permissions = $registry->all();

        $permissions->each(fn (array $permission) => Permission::query()->updateOrCreate(
            ['key' => $permission['key']],
            $permission,
        ));

        $this->components->info("Synced {$permissions->count()} permissions.");

        return self::SUCCESS;
    }
}
