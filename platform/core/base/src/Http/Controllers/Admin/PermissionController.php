<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Support\PermissionRegistry;

class PermissionController extends Controller
{
    public function __construct(private readonly PermissionRegistry $permissions) {}

    public function index(): View
    {
        $this->syncRegisteredPermissions();

        $permissions = Permission::query()
            ->orderBy('module')
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        return view('core/base::admin.permissions.index', [
            'permissions' => $permissions,
            'permissionModules' => $permissions->groupBy(fn (Permission $permission): string => $permission->module),
            'registeredPermissions' => $this->permissions->all(),
        ]);
    }

    private function syncRegisteredPermissions(): void
    {
        $this->permissions->all()->each(fn (array $permission) => Permission::query()->updateOrCreate(
            ['key' => $permission['key']],
            $permission,
        ));
    }
}
