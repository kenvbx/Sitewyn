<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Models\Setting;
use Sitewyn\Core\Base\Support\SettingStore;
use Tests\TestCase;

class AdminCronjobTest extends TestCase
{
    use RefreshDatabase;

    public function test_cronjob_route_uses_system_url_prefix(): void
    {
        $this->assertTrue(Route::has('admin.system.cronjob'));
        $this->assertSame('/admin/system/cronjob', route('admin.system.cronjob', [], false));
    }

    public function test_guest_is_redirected_from_cronjob_page(): void
    {
        $this->get('/admin/system/cronjob')->assertRedirect('/admin/login');
    }

    public function test_admin_without_permission_is_forbidden(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/system/cronjob')
            ->assertForbidden();
    }

    public function test_admin_with_permission_can_view_cronjob_setup(): void
    {
        $content = $this->actingAs($this->userWithPermissions(['cronjobs.manage']), 'admin')
            ->get('/admin/system/cronjob')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Cronjob is not configured yet', $content);
        $this->assertStringContainsString('What is a Cronjob?', $content);
        $this->assertStringContainsString('Your Cronjob Command', $content);
        $this->assertStringContainsString('schedule:run', $content);
        $this->assertStringContainsString('Copy Command', $content);
        $this->assertStringContainsString('cPanel', $content);
        $this->assertStringContainsString('Plesk', $content);
        $this->assertStringContainsString('DirectAdmin', $content);
        $this->assertStringContainsString('SSH/Terminal', $content);
    }

    public function test_cronjob_page_shows_running_alert_when_recent_heartbeat_exists(): void
    {
        Date::setTestNow('2026-09-01 22:30:00');

        app(SettingStore::class)->setMany([
            'cronjob_last_run_at' => now()->subMinute()->toISOString(),
        ], 'system');

        $this->actingAs($this->userWithPermissions(['cronjobs.manage']), 'admin')
            ->get('/admin/system/cronjob')
            ->assertOk()
            ->assertSee('Cronjob is running')
            ->assertSee('Last run: 2026-09-01 22:29:00')
            ->assertDontSee('Cronjob is not configured yet');

        Date::setTestNow();
    }

    public function test_cronjob_page_shows_warning_when_heartbeat_is_stale(): void
    {
        Date::setTestNow('2026-09-01 22:30:00');

        app(SettingStore::class)->setMany([
            'cronjob_last_run_at' => now()->subMinutes(5)->toISOString(),
        ], 'system');

        $this->actingAs($this->userWithPermissions(['cronjobs.manage']), 'admin')
            ->get('/admin/system/cronjob')
            ->assertOk()
            ->assertSee('Cronjob is not configured yet')
            ->assertDontSee('Cronjob is running');

        Date::setTestNow();
    }

    public function test_cron_heartbeat_command_records_latest_run_time(): void
    {
        Date::setTestNow('2026-09-01 22:30:00');

        Artisan::call('system:cron-heartbeat');

        $this->assertDatabaseHas('settings', [
            'key' => 'cronjob_last_run_at',
            'group' => 'system',
        ]);

        $this->assertSame(now()->toISOString(), Setting::query()->where('key', 'cronjob_last_run_at')->value('value'));

        Date::setTestNow();
    }

    public function test_platform_hub_shows_cronjob_card_only_with_permission(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertDontSee('href="/admin/system/cronjob"', false);

        $this->actingAs($this->userWithPermissions(['cronjobs.manage']), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertSee('href="/admin/system/cronjob"', false)
            ->assertSee('Set up automated background tasks to keep your website running smoothly.');
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
            $role->permissions()->attach(Permission::factory()->create([
                'key' => $key,
            ]));
        }

        $user->roles()->attach($role);

        return $user;
    }
}
