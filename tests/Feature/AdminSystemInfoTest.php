<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Tests\TestCase;

class AdminSystemInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_info_route_uses_system_url_prefix(): void
    {
        $this->assertTrue(Route::has('admin.system.info'));
        $this->assertSame('/admin/system/info', route('admin.system.info', [], false));
    }

    public function test_guest_is_redirected_from_system_info_page(): void
    {
        $this->get('/admin/system/info')->assertRedirect('/admin/login');
    }

    public function test_admin_without_permission_is_forbidden(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/system/info')
            ->assertForbidden();
    }

    public function test_admin_with_permission_can_view_system_information(): void
    {
        $content = $this->actingAs($this->userWithPermissions(['system.info']), 'admin')
            ->get('/admin/system/info')
            ->assertOk()
            ->getContent();

        foreach ([
            'Please share this information for troubleshooting',
            'Get System Report',
            'Installed packages and their version numbers',
            'Search...',
            'System Environment',
            'Server Environment',
            'Database Information',
            'PHP Configuration',
            'Framework Version',
            'PHP Version',
            'Database Driver',
            'POST Max Size',
            'Copy Report',
            'laravel/framework',
        ] as $text) {
            $this->assertStringContainsString($text, $content);
        }
    }

    public function test_platform_hub_shows_system_info_card_only_with_permission(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertDontSee('href="/admin/system/info"', false);

        $this->actingAs($this->userWithPermissions(['system.info']), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertSee('href="/admin/system/info"', false)
            ->assertSee('All information about current system configuration.');
    }

    private function plainAdmin(): User
    {
        return User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = $this->plainAdmin();
        $role = Role::factory()->create();

        foreach ($permissions as $key) {
            $role->permissions()->attach(Permission::query()->firstOrCreate(
                ['key' => $key],
                [
                    'name' => $key,
                    'module' => 'core/base',
                    'group' => str($key)->before('.')->toString(),
                    'description' => null,
                ],
            ));
        }

        $user->roles()->attach($role);

        return $user;
    }
}
