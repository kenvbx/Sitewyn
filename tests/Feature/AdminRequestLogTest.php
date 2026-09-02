<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\RequestLog;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Tests\TestCase;

class AdminRequestLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_requests_are_recorded(): void
    {
        $this->get('/missing-page')->assertNotFound();

        $this->assertDatabaseHas('request_logs', [
            'url' => 'http://localhost:8000/missing-page',
            'method' => 'GET',
            'status_code' => 404,
        ]);
    }

    public function test_request_log_index_requires_view_permission(): void
    {
        $this->get('/admin/request-logs')->assertRedirect('/admin/login');

        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/request-logs')
            ->assertForbidden();

        $this->actingAs($this->userWithPermissions(['request_logs.index']), 'admin')
            ->get('/admin/request-logs')
            ->assertOk();
    }

    public function test_request_log_index_groups_rows_by_url_and_status_code(): void
    {
        $viewer = $this->userWithPermissions(['request_logs.index']);

        RequestLog::query()->create(['url' => 'https://example.test/missing', 'method' => 'GET', 'status_code' => 404]);
        RequestLog::query()->create(['url' => 'https://example.test/missing', 'method' => 'GET', 'status_code' => 404]);
        RequestLog::query()->create(['url' => 'https://example.test/denied', 'method' => 'POST', 'status_code' => 405]);

        $this->actingAs($viewer, 'admin')
            ->get('/admin/request-logs')
            ->assertOk()
            ->assertSee('Request Logs')
            ->assertSee('Bulk Actions')
            ->assertSee('Delete all records')
            ->assertSee('Reload')
            ->assertSee('https://example.test/missing')
            ->assertSee('https://example.test/denied')
            ->assertSee('404')
            ->assertSee('405')
            ->assertSee('>2<', false);
    }

    public function test_request_log_search_filters_by_url(): void
    {
        $viewer = $this->userWithPermissions(['request_logs.index']);

        RequestLog::query()->create(['url' => 'https://example.test/visible', 'method' => 'GET', 'status_code' => 404]);
        RequestLog::query()->create(['url' => 'https://example.test/hidden', 'method' => 'GET', 'status_code' => 500]);

        $this->actingAs($viewer, 'admin')
            ->get('/admin/request-logs?search=visible')
            ->assertOk()
            ->assertSee('https://example.test/visible')
            ->assertDontSee('https://example.test/hidden');
    }

    public function test_delete_permission_can_delete_one_group(): void
    {
        $viewer = $this->userWithPermissions(['request_logs.index', 'request_logs.delete']);
        $first = RequestLog::query()->create(['url' => 'https://example.test/missing', 'method' => 'GET', 'status_code' => 404]);
        RequestLog::query()->create(['url' => 'https://example.test/missing', 'method' => 'POST', 'status_code' => 404]);
        RequestLog::query()->create(['url' => 'https://example.test/kept', 'method' => 'GET', 'status_code' => 404]);

        $this->actingAs($viewer, 'admin')
            ->delete('/admin/request-logs/'.$first->id)
            ->assertRedirect('/admin/request-logs');

        $this->assertDatabaseMissing('request_logs', ['url' => 'https://example.test/missing']);
        $this->assertDatabaseHas('request_logs', ['url' => 'https://example.test/kept']);
    }

    public function test_bulk_delete_and_clear_require_delete_permission(): void
    {
        $viewer = $this->userWithPermissions(['request_logs.index']);
        $log = RequestLog::query()->create(['url' => 'https://example.test/missing', 'method' => 'GET', 'status_code' => 404]);

        $this->actingAs($viewer, 'admin')
            ->delete('/admin/request-logs/bulk-destroy', ['ids' => [$log->id]])
            ->assertForbidden();

        $this->actingAs($this->userWithPermissions(['request_logs.index', 'request_logs.delete']), 'admin')
            ->delete('/admin/request-logs/bulk-destroy', ['ids' => [$log->id]])
            ->assertRedirect('/admin/request-logs');

        $this->assertDatabaseCount('request_logs', 0);

        RequestLog::query()->create(['url' => 'https://example.test/one', 'method' => 'GET', 'status_code' => 404]);
        RequestLog::query()->create(['url' => 'https://example.test/two', 'method' => 'GET', 'status_code' => 500]);

        $this->actingAs($this->userWithPermissions(['request_logs.index', 'request_logs.delete']), 'admin')
            ->delete('/admin/request-logs/clear')
            ->assertRedirect('/admin/request-logs');

        $this->assertDatabaseCount('request_logs', 0);
    }

    public function test_request_logs_are_not_in_sidebar_but_platform_card_requires_view_permission(): void
    {
        $registry = $this->app->make(AdminMenuRegistry::class);
        $viewer = $this->userWithPermissions(['request_logs.index']);

        $this->assertTrue(Route::has('admin.request-logs.index'));
        $this->assertSame('/admin/request-logs', route('admin.request-logs.index', [], false));
        $this->assertFalse($registry->has('request-logs'));
        $this->assertFalse($registry->visibleFor($viewer)->pluck('id')->contains('request-logs'));
        $this->assertFalse($registry->visibleFor($this->plainAdmin())->pluck('id')->contains('request-logs'));

        $this->actingAs($viewer, 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertSee('View and delete your system request logs.');
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
            $permission = Permission::query()->firstOrCreate(
                ['key' => $key],
                [
                    'name' => $key,
                    'module' => 'core/base',
                    'group' => 'request logs',
                    'description' => null,
                ],
            );

            $role->permissions()->attach($permission);
        }

        $user->roles()->attach($role);

        return $user;
    }
}
