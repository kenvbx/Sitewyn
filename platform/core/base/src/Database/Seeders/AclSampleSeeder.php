<?php

namespace Sitewyn\Core\Base\Database\Seeders;

use Illuminate\Database\Seeder;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Support\PermissionRegistry;

class AclSampleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = app(PermissionRegistry::class)
            ->all()
            ->whereIn('key', ['users.index', 'roles.index'])
            ->map(fn (array $permission) => Permission::query()->updateOrCreate(
                ['key' => $permission['key']],
                $permission,
            ));

        $role = Role::query()->updateOrCreate(
            ['slug' => 'content-manager'],
            [
                'name' => 'Content Manager',
                'description' => 'Sample role for validating ACL relationships.',
                'is_system' => false,
            ],
        );

        $role->permissions()->sync($permissions->pluck('id')->all());
    }
}
