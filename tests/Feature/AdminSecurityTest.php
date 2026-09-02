<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_route_uses_system_url_prefix(): void
    {
        $this->assertTrue(Route::has('admin.system.security'));
        $this->assertSame('/admin/system/security', route('admin.system.security', [], false));
    }

    public function test_guest_is_redirected_from_security_page(): void
    {
        $this->get('/admin/system/security')->assertRedirect('/admin/login');
    }

    public function test_admin_without_permission_is_forbidden(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/system/security')
            ->assertForbidden();
    }

    public function test_admin_with_permission_can_view_security_settings(): void
    {
        config([
            'session.http_only' => true,
            'session.secure' => true,
            'session.same_site' => 'lax',
        ]);

        $content = $this->actingAs($this->userWithPermissions(['security.manage']), 'admin')
            ->get('/admin/system/security')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-XSS-Protection', '1; mode=block')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->getContent();

        $this->assertStringContainsString('All security settings are properly configured!', $content);
        $this->assertStringContainsString('Current Security Settings', $content);
        $this->assertStringContainsString('HttpOnly Cookie Flag', $content);
        $this->assertStringContainsString('Secure Cookie Flag', $content);
        $this->assertStringContainsString('SameSite Cookie Flag', $content);
        $this->assertStringContainsString('HTTP Security Headers', $content);
        $this->assertStringContainsString('Security Headers Information', $content);
        $this->assertStringContainsString('X-Content-Type-Options: nosniff', $content);
    }

    public function test_security_page_warns_when_a_setting_does_not_match_recommendation(): void
    {
        config([
            'session.http_only' => true,
            'session.secure' => false,
            'session.same_site' => 'lax',
        ]);

        $this->actingAs($this->userWithPermissions(['security.manage']), 'admin')
            ->get('/admin/system/security')
            ->assertOk()
            ->assertSee('Some security settings need attention')
            ->assertDontSee('All security settings are properly configured!');
    }

    public function test_platform_hub_shows_security_card_only_with_permission(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertDontSee('href="/admin/system/security"', false);

        $this->actingAs($this->userWithPermissions(['security.manage']), 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertSee('href="/admin/system/security"', false)
            ->assertSee('Manage cookie security and HTTP headers');
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
