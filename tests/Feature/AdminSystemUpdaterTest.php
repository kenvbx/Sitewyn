<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Tests\TestCase;

class AdminSystemUpdaterTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_updater_route_uses_system_url_prefix(): void
    {
        $this->assertTrue(Route::has('admin.system.updater.index'));
        $this->assertSame('/admin/system/updater', route('admin.system.updater.index', [], false));
    }

    public function test_guest_is_redirected_from_system_updater_page(): void
    {
        $this->get('/admin/system/updater')->assertRedirect('/admin/login');
    }

    public function test_admin_without_permission_is_forbidden(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/system/updater')
            ->assertForbidden();
    }

    public function test_admin_with_permission_can_view_system_updater(): void
    {
        $content = $this->actingAs($this->userWithPermissions(['system.updater']), 'admin')
            ->get('/admin/system/updater')
            ->assertOk()
            ->getContent();

        foreach ([
            'Important notes:',
            'CMS_ENABLE_SYSTEM_UPDATER=false',
            'OneClick System Updater',
            'The system is up-to-date. There are no new versions to update!',
            'Re-install The Latest Version',
            'Manual System Updater',
            'Download update files',
            'Update system files',
            'Update databases',
            'Publish core assets',
            'Publish packages assets',
            'Clean up system update files',
            'Latest changelog',
        ] as $text) {
            $this->assertStringContainsString($text, $content);
        }
    }

    public function test_reinstall_marks_all_manual_steps_complete(): void
    {
        $this->actingAs($this->userWithPermissions(['system.updater']), 'admin')
            ->post('/admin/system/updater/reinstall')
            ->assertRedirect('/admin/system/updater')
            ->assertSessionHas('status')
            ->assertSessionHas('system_updater.completed_steps.6');
    }

    public function test_manual_step_can_be_run(): void
    {
        $this->actingAs($this->userWithPermissions(['system.updater']), 'admin')
            ->post('/admin/system/updater/steps/3')
            ->assertRedirect('/admin/system/updater')
            ->assertSessionHas('status', 'Update databases completed.')
            ->assertSessionHas('system_updater.completed_steps.3.title', 'Update databases');
    }

    public function test_platform_hub_shows_system_updater_card_only_with_permission(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertDontSee('href="/admin/system/updater"', false);

        $this->actingAs($this->userWithPermissions(['system.updater']), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertSee('href="/admin/system/updater"', false)
            ->assertSee('Update your system to the latest version.');
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
