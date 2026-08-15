<?php

namespace Sitewyn\Core\Base\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Sitewyn\Core\Base\Models\Permission;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $group = fake()->randomElement(['users', 'roles', 'settings']);
        $action = fake()->randomElement(['index', 'create', 'edit', 'delete', 'publish', 'restore']);
        $key = $group.'.'.$action.'.'.fake()->unique()->numberBetween(1000, 9999);

        return [
            'name' => fake()->words(2, true),
            'key' => $key,
            'module' => 'core/base',
            'group' => $group,
            'description' => fake()->sentence(),
        ];
    }
}
