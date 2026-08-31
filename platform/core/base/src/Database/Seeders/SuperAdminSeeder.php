<?php

namespace Sitewyn\Core\Base\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Sitewyn\Core\Base\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::query()->updateOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Built-in role with unrestricted CMS access.',
                'is_system' => true,
            ],
        );

        // Built-in Admin role: marks team members for the user management area
        // (Users card on the Platform Administration hub, /admin/users list).
        // It deliberately ships with no permissions — super admins assign them
        // through the Roles UI. Recreated on every db:seed run.
        Role::query()->firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Built-in team role for user management; assign permissions through the Roles UI.',
                'is_system' => true,
            ],
        );

        $user = User::query()->updateOrCreate(
            ['email' => $this->adminEmail()],
            [
                'name' => $this->adminName(),
                'username' => $this->adminUsername(),
                'password' => Hash::make($this->adminPassword()),
                'is_super_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    private function adminName(): string
    {
        $name = config('sitewyn-base.admin.name');

        return is_string($name) && $name !== '' ? $name : 'Super Admin';
    }

    private function adminEmail(): string
    {
        return $this->configOrPrompt('sitewyn-base.admin.email', 'Admin email', 'admin@example.com');
    }

    private function adminUsername(): string
    {
        $username = config('sitewyn-base.admin.username');

        if (is_string($username) && $username !== '') {
            return $username;
        }

        return str($this->adminEmail())->before('@')->slug()->toString();
    }

    private function adminPassword(): string
    {
        return $this->configOrPrompt('sitewyn-base.admin.password', 'Admin password', 'password');
    }

    private function configOrPrompt(string $key, string $question, string $default): string
    {
        $value = config($key);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        // Prompt only when a human is attached to the terminal — the same
        // interactivity rule ConfiguresPrompts uses. The seed command exposes
        // no usable input API for seeders, so detect a TTY directly; CI,
        // tests and --no-interaction runs fall back to the default.
        if (defined('STDIN') && stream_isatty(STDIN)) {
            $answer = $key === 'sitewyn-base.admin.password'
                ? $this->command->secret($question)
                : $this->command->ask($question, $default);

            if (is_string($answer) && $answer !== '') {
                return $answer;
            }
        }

        return $default;
    }
}
