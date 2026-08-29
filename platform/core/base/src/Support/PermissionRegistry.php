<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Support\Collection;
use InvalidArgumentException;

class PermissionRegistry
{
    /**
     * @var array<string, array{name: string, key: string, module: string, group: string|null, description: string|null}>
     */
    private array $permissions = [];

    /**
     * @param  array<int, array{name?: string, key?: string, module?: string, group?: string|null, description?: string|null}>  $permissions
     */
    public function register(array $permissions, ?string $module = null, ?string $group = null): void
    {
        foreach ($permissions as $permission) {
            $this->add(
                key: (string) ($permission['key'] ?? ''),
                name: (string) ($permission['name'] ?? ''),
                module: (string) ($permission['module'] ?? $module ?? ''),
                group: $permission['group'] ?? $group,
                description: $permission['description'] ?? null,
            );
        }
    }

    public function add(
        string $key,
        string $name,
        string $module,
        ?string $group = null,
        ?string $description = null,
    ): void {
        if ($key === '' || $name === '' || $module === '') {
            throw new InvalidArgumentException('Permission key, name, and module are required.');
        }

        $this->permissions[$key] = [
            'name' => $name,
            'key' => $key,
            'module' => $module,
            'group' => $group,
            'description' => $description,
        ];
    }

    /**
     * @return Collection<int, array{name: string, key: string, module: string, group: string|null, description: string|null}>
     */
    public function all(): Collection
    {
        return collect($this->permissions)
            ->sortKeys()
            ->values();
    }

    /**
     * @return Collection<string, Collection<int, array{name: string, key: string, module: string, group: string|null, description: string|null}>>
     */
    public function grouped(): Collection
    {
        return $this->all()->groupBy(fn (array $permission): string => $permission['group'] ?? 'ungrouped');
    }

    public function has(string $key): bool
    {
        return isset($this->permissions[$key]);
    }
}
