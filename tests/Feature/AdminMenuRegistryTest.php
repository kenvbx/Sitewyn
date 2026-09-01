<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Tests\TestCase;

class AdminMenuRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_base_registers_default_admin_menu_items(): void
    {
        $registry = $this->app->make(AdminMenuRegistry::class);

        $this->assertTrue($registry->has('dashboard'));
        $this->assertTrue($registry->has('access-control'));
        $this->assertTrue($registry->has('pages'));
        $this->assertTrue($registry->has('posts'));
        $this->assertTrue($registry->has('categories'));
        $this->assertTrue($registry->has('tags'));
        $this->assertTrue($registry->has('menus'));
        $this->assertTrue($registry->has('widgets'));
        $this->assertTrue($registry->has('media'));
        $this->assertTrue($registry->has('plugins'));
        $this->assertTrue($registry->has('audit-logs'));
        $this->assertTrue($registry->has('backups'));
        $this->assertTrue($registry->has('settings'));
        $this->assertSame(['dashboard', 'pages', 'access-control', 'posts', 'categories', 'tags', 'menus', 'widgets', 'media', 'plugins', 'audit-logs', 'backups', 'settings', 'system'], $registry->all()->pluck('id')->all());
        $this->assertSame(['users', 'permissions'], collect($registry->all()[2]['children'])->pluck('id')->all());
    }

    public function test_sidebar_hides_menu_items_without_permission(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);
        $role = Role::factory()->create();
        $permission = Permission::factory()->create([
            'key' => 'users.index',
        ]);

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->actingAs($user, 'admin')
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Access Control')
            ->assertSee('href="http://localhost:8000/admin/users"', false)
            ->assertDontSee('href="http://localhost:8000/admin/system/roles"', false)
            ->assertDontSee('href="http://localhost:8000/admin/permissions"', false)
            ->assertDontSee('Roles');
    }

    public function test_empty_menu_groups_are_hidden(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin');

        $html = Blade::render(
            <<<'BLADE'
            @extends('core/base::admin.layouts.master')

            @section('page-title', 'Menu Test')
            @section('content')
              <div>Menu content</div>
            @endsection
            BLADE,
            [],
            deleteCachedView: true,
        );

        $this->assertStringContainsString('Dashboard', $html);
        $this->assertStringNotContainsString('Access Control', $html);
    }
}
