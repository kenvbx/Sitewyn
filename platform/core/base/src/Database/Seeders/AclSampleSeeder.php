<?php

namespace Sitewyn\Core\Base\Database\Seeders;

use Illuminate\Database\Seeder;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;

class AclSampleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            [
                'name' => 'View users',
                'key' => 'users.index',
                'module' => 'core/base',
                'group' => 'users',
                'description' => 'View admin user list.',
            ],
            [
                'name' => 'View roles',
                'key' => 'roles.index',
                'module' => 'core/base',
                'group' => 'roles',
                'description' => 'View admin role list.',
            ],
        ])->map(fn (array $permission) => Permission::query()->updateOrCreate(
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
