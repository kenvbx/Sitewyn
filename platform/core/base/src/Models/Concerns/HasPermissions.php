<?php

namespace Sitewyn\Core\Base\Models\Concerns;

use Illuminate\Support\Collection;
use Sitewyn\Core\Base\Models\Permission;

trait HasPermissions
{
    public function hasPermission(string|Permission $permission): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $key = $permission instanceof Permission ? $permission->key : $permission;

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('key', $key))
            ->exists();
    }

    /**
     * @param  iterable<int, string|Permission>  $permissions
     */
    public function hasAnyPermission(iterable $permissions): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $keys = $this->permissionKeysFrom($permissions);

        if ($keys->isEmpty()) {
            return false;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->whereIn('key', $keys->all()))
            ->exists();
    }

    /**
     * @param  iterable<int, string|Permission>  $permissions
     */
    public function hasAllPermissions(iterable $permissions): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $keys = $this->permissionKeysFrom($permissions);

        if ($keys->isEmpty()) {
            return false;
        }

        $matchedCount = $this->roles()
            ->join('permission_role', 'roles.id', '=', 'permission_role.role_id')
            ->join('permissions', 'permissions.id', '=', 'permission_role.permission_id')
            ->whereIn('permissions.key', $keys->all())
            ->distinct('permissions.key')
            ->count('permissions.key');

        return $matchedCount === $keys->count();
    }

    /**
     * @return Collection<int, string>
     */
    public function permissionKeys(): Collection
    {
        if ($this->is_super_admin) {
            return Permission::query()
                ->orderBy('key')
                ->pluck('key')
                ->values();
        }

        return Permission::query()
            ->whereHas('roles.users', fn ($query) => $query->whereKey($this->getKey()))
            ->orderBy('key')
            ->pluck('key')
            ->unique()
            ->values();
    }

    /**
     * @param  iterable<int, string|Permission>  $permissions
     * @return Collection<int, string>
     */
    private function permissionKeysFrom(iterable $permissions): Collection
    {
        return collect($permissions)
            ->map(fn (string|Permission $permission): string => $permission instanceof Permission ? $permission->key : $permission)
            ->filter()
            ->unique()
            ->values();
    }
}
