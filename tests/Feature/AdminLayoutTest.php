<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AdminLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_crud_pages_use_master_layout_chrome(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('navbar-vertical', false)
            ->assertSee('Access Control')
            ->assertSee('breadcrumb-arrows')
            ->assertSee('Users');
    }

    public function test_dashboard_and_users_share_the_same_sidebar_layout(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $dashboard = $this->actingAs($user, 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertSee('navbar-vertical', false)
            ->assertSee('Dashboard')
            ->assertSee('Access Control')
            ->assertSee('Users')
            ->content();

        $users = $this->actingAs($user, 'admin')
            ->get('/admin/users')
            ->assertOk()
            ->content();

        $this->assertSame(1, substr_count($dashboard, 'id="sidebar-menu"'));
        $this->assertStringContainsString('core/base::admin.layouts.master', file_get_contents(base_path('platform/core/base/resources/views/admin/dashboard.blade.php')));
        $this->assertStringContainsString('Access Control', $users);
    }

    public function test_legacy_app_layout_alias_extends_master_layout(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin');

        $html = Blade::render(
            <<<'BLADE'
            @extends('core/base::admin.layouts.app')

            @section('title', 'Alias Layout')
            @section('pretitle', 'Alias')
            @section('page-title', 'Alias Page')
            @section('content')
              <div>Alias content</div>
            @endsection
            BLADE,
            [],
            deleteCachedView: true,
        );

        $this->assertStringContainsString('navbar-vertical', $html);
        $this->assertStringContainsString('Alias Page', $html);
        $this->assertStringContainsString('Alias content', $html);
    }
}
