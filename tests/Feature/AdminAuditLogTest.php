<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Models\AuditLog;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Sitewyn\Core\Base\Support\AuditLogger;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_page_records_created_entry_with_user_and_subject(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin, 'admin');

        $page = Page::query()->create([
            'title' => 'About us',
            'slug' => 'about-us',
            'status' => Page::STATUS_DRAFT,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'subject_type' => Page::class,
            'subject_id' => $page->id,
        ]);

        $log = AuditLog::query()->where('action', 'created')->where('subject_type', Page::class)->firstOrFail();

        $this->assertSame('About us', $log->properties['attributes']['title']);
        $this->assertArrayNotHasKey('password', $log->properties['attributes']);
        $this->assertNotNull($log->created_at);
    }

    public function test_updating_a_page_records_only_the_changed_fields(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin, 'admin');

        $page = Page::query()->create([
            'title' => 'About us',
            'slug' => 'about-us',
            'content' => '<p>Old body</p>',
            'status' => Page::STATUS_DRAFT,
        ]);

        $page->update(['title' => 'About Sitewyn']);

        $log = AuditLog::query()->where('action', 'updated')->where('subject_id', $page->id)->firstOrFail();

        // Only the edited field is logged (plus the subject id), so the
        // history stays readable instead of echoing the whole row.
        $this->assertSame(['id', 'title'], array_keys($log->properties['changes']));
        $this->assertSame($page->id, $log->properties['changes']['id']);
        $this->assertSame('About Sitewyn', $log->properties['changes']['title']);
    }

    public function test_deleting_a_page_records_the_old_attributes(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin, 'admin');

        $page = Page::query()->create([
            'title' => 'About us',
            'slug' => 'about-us',
            'status' => Page::STATUS_DRAFT,
        ]);
        $page->delete();

        $log = AuditLog::query()->where('action', 'deleted')->where('subject_id', $page->id)->firstOrFail();

        $this->assertSame(Page::class, $log->subject_type);
        $this->assertSame('About us', $log->properties['attributes']['title']);
        $this->assertSame('about-us', $log->properties['attributes']['slug']);
    }

    public function test_successful_admin_login_is_recorded(): void
    {
        $admin = $this->adminUser();

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect();

        $log = AuditLog::query()->where('action', 'login')->firstOrFail();

        $this->assertSame(User::class, $log->subject_type);
        $this->assertSame($admin->id, $log->subject_id);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertNotNull($log->ip_address);
    }

    public function test_admin_logout_is_recorded(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->post('/admin/logout')
            ->assertRedirect();

        $log = AuditLog::query()->where('action', 'logout')->firstOrFail();

        $this->assertSame(User::class, $log->subject_type);
        $this->assertSame($admin->id, $log->subject_id);
        $this->assertSame($admin->id, $log->user_id);
    }

    public function test_failed_admin_login_records_the_attempted_email(): void
    {
        $this->post('/admin/login', [
            'email' => 'ghost@example.com',
            'password' => 'wrong-password',
        ]);

        $log = AuditLog::query()->where('action', 'login-failed')->firstOrFail();

        // A failed attempt has no authenticated subject: only the attempted
        // email identifies who tried to get in.
        $this->assertNull($log->subject_id);
        $this->assertSame('ghost@example.com', $log->properties['email']);
    }

    public function test_sensitive_properties_are_stripped_before_logging(): void
    {
        $logger = $this->app->make(AuditLogger::class);

        $logger->record('updated', Page::class, 1, [
            'title' => 'Kept',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'context' => [
                'remember_token' => 'token-value',
                'note' => 'kept too',
            ],
        ]);

        $log = AuditLog::query()->firstOrFail();

        $this->assertSame('Kept', $log->properties['title']);
        $this->assertArrayNotHasKey('password', $log->properties);
        $this->assertArrayNotHasKey('password_confirmation', $log->properties);
        $this->assertArrayNotHasKey('remember_token', $log->properties['context']);
        $this->assertSame('kept too', $log->properties['context']['note']);
    }

    public function test_guest_is_redirected_from_audit_index(): void
    {
        $this->get('/admin/audit-logs')
            ->assertRedirect('/admin/login');
    }

    public function test_audit_index_requires_audit_index_permission(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/audit-logs')
            ->assertForbidden();

        $this->actingAs($this->userWithPermissions(['audit.index']), 'admin')
            ->get('/admin/audit-logs')
            ->assertOk();
    }

    public function test_audit_index_shows_entries_for_permitted_user(): void
    {
        $viewer = $this->userWithPermissions(['audit.index']);
        $admin = $this->adminUser();
        $this->actingAs($admin, 'admin');

        $this->app->make(AuditLogger::class)->record('created', Page::class, 5, [
            'attributes' => ['title' => 'About us'],
        ]);

        $this->actingAs($viewer, 'admin')
            ->get('/admin/audit-logs')
            ->assertOk()
            ->assertSee($admin->name)
            ->assertSee('Page #5')
            ->assertSee('About us');
    }

    public function test_audit_index_paginates_fifty_entries_per_page(): void
    {
        $viewer = $this->userWithPermissions(['audit.index']);

        for ($i = 1; $i <= 60; $i++) {
            AuditLog::query()->create([
                'action' => 'created',
                'subject_type' => Page::class,
                'subject_id' => $i,
            ]);
        }

        // 60 page entries plus the audited "created" entry for the viewer account.
        $this->assertSame(60, AuditLog::query()->where('subject_type', Page::class)->count());
        $this->assertSame(61, AuditLog::query()->count());

        $firstPage = $this->actingAs($viewer, 'admin')->get('/admin/audit-logs')->getContent();
        $this->assertSame(50, substr_count($firstPage, 'data-bs-target="#audit-log-'));
        $this->assertStringContainsString('?page=2', $firstPage);

        $secondPage = $this->actingAs($viewer, 'admin')->get('/admin/audit-logs?page=2')->getContent();
        $this->assertSame(11, substr_count($secondPage, 'data-bs-target="#audit-log-'));
    }

    public function test_audit_index_filters_by_action(): void
    {
        $viewer = $this->userWithPermissions(['audit.index']);

        $created = AuditLog::query()->create([
            'action' => 'created',
            'subject_type' => Page::class,
            'subject_id' => 1,
        ]);
        $login = AuditLog::query()->create([
            'action' => 'login',
            'subject_type' => User::class,
            'subject_id' => 2,
        ]);

        $this->actingAs($viewer, 'admin')
            ->get('/admin/audit-logs?action=login')
            ->assertOk()
            ->assertSee('#audit-log-'.$login->id.'"', false)
            ->assertDontSee('#audit-log-'.$created->id.'"', false);
    }

    public function test_audit_logs_menu_item_requires_audit_index_permission(): void
    {
        $registry = $this->app->make(AdminMenuRegistry::class);

        $this->assertTrue($registry->has('audit-logs'));

        $viewer = $this->userWithPermissions(['audit.index']);

        $this->assertTrue($registry->visibleFor($viewer)->pluck('id')->contains('audit-logs'));
        $this->assertFalse($registry->visibleFor($this->plainAdmin())->pluck('id')->contains('audit-logs'));
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
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
                    'group' => 'audit',
                    'description' => null,
                ],
            );

            $role->permissions()->attach($permission);
        }

        $user->roles()->attach($role);

        return $user;
    }
}
