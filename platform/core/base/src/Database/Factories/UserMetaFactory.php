<?php

namespace Sitewyn\Core\Base\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Sitewyn\Core\Base\Models\UserMeta;

/**
 * @extends Factory<UserMeta>
 */
class UserMetaFactory extends Factory
{
    protected $model = UserMeta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'key' => fake()->randomElement(['locale', 'timezone', 'admin_theme']).'_'.fake()->unique()->numberBetween(1000, 9999),
            'value' => fake()->word(),
        ];
    }
}
