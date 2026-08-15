<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Database\Seeders\AclSampleSeeder;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Models\UserMeta;
use Tests\TestCase;

class AclModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_permission_relationships_work(): void
    {
        $user = User::factory()->create([
            'username' => 'editor',
            'is_active' => true,
        ]);

        $role = Role::factory()->create([
            'slug' => 'editor',
        ]);

        $permission = Permission::factory()->create([
            'key' => 'posts.edit',
            'module' => 'core/base',
            'group' => 'posts',
        ]);

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->assertTrue($user->roles()->whereKey($role->id)->exists());
        $this->assertTrue($role->users()->whereKey($user->id)->exists());
        $this->assertTrue($role->permissions()->whereKey($permission->id)->exists());
        $this->assertTrue($permission->roles()->whereKey($role->id)->exists());
    }

    public function test_user_meta_relationship_works(): void
    {
        $user = User::factory()->create();

        $meta = UserMeta::factory()->create([
            'user_id' => $user->id,
            'key' => 'locale',
            'value' => 'vi',
        ]);

        $this->assertTrue($user->meta()->whereKey($meta->id)->exists());
        $this->assertTrue($meta->user()->is($user));
    }

    public function test_acl_sample_seeder_is_idempotent(): void
    {
        $this->seed(AclSampleSeeder::class);
        $this->seed(AclSampleSeeder::class);

        $role = Role::query()->where('slug', 'content-manager')->firstOrFail();

        $this->assertSame(1, Role::query()->where('slug', 'content-manager')->count());
        $this->assertSame(1, Permission::query()->where('key', 'users.index')->count());
        $this->assertSame(1, Permission::query()->where('key', 'roles.index')->count());
        $this->assertTrue($role->permissions()->where('key', 'users.index')->exists());
        $this->assertTrue($role->permissions()->where('key', 'roles.index')->exists());
    }
}
