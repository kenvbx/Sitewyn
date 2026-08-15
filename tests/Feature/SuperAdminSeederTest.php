<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Sitewyn\Core\Base\Database\Seeders\SuperAdminSeeder;
use Sitewyn\Core\Base\Models\Role;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_seeder_creates_default_admin_account(): void
    {
        config([
            'sitewyn-base.admin.name' => 'Owner',
            'sitewyn-base.admin.username' => 'owner',
            'sitewyn-base.admin.email' => 'owner@example.com',
            'sitewyn-base.admin.password' => 'secret-password',
        ]);

        $this->seed(SuperAdminSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $role = Role::query()->where('slug', 'super-admin')->firstOrFail();

        $this->assertSame('Owner', $user->name);
        $this->assertSame('owner', $user->username);
        $this->assertTrue($user->is_super_admin);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertTrue($role->is_system);
        $this->assertTrue($user->roles()->whereKey($role->id)->exists());
        $this->assertSame(1, User::query()->where('email', 'owner@example.com')->count());
        $this->assertSame(1, Role::query()->where('slug', 'super-admin')->count());
    }

    public function test_database_seeder_creates_super_admin_account(): void
    {
        config([
            'sitewyn-base.admin.email' => 'admin@example.com',
            'sitewyn-base.admin.password' => 'password',
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(User::query()->where('email', 'admin@example.com')->exists());
        $this->assertTrue(Role::query()->where('slug', 'super-admin')->exists());
    }
}
