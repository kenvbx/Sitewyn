<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Sitewyn\Core\Base\Models\Setting;
use Sitewyn\Core\Base\Support\SettingStore;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_requires_permission(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_super_admin_can_view_settings_hub(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Settings')
            ->assertSee('Common')
            ->assertSee('General')
            ->assertSee('View and update your general settings and activate license')
            ->assertSee('Email templates')
            ->assertSee('Website Tracking')
            ->assertSee('Localization')
            ->assertSee('Theme Translations')
            ->assertSee('Others')
            ->assertSee('Google Analytics');
    }

    public function test_super_admin_can_update_general_settings_and_refresh_cache(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        Cache::put('sitewyn.settings', [
            'site_name' => 'Cached Name',
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings')
            ->put('/admin/settings', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => '/storage/site-logo.svg',
            ])
            ->assertRedirect('/admin/settings')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', [
            'key' => 'site_name',
            'value' => 'Sitewyn Personal',
            'group' => 'general',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'site_logo',
            'value' => '/storage/site-logo.svg',
            'group' => 'general',
        ]);
        $this->assertSame('Sitewyn Personal', app(SettingStore::class)->get('site_name'));
        $this->assertSame('Sitewyn Personal', config('app.name'));
    }

    public function test_settings_validation_keeps_site_name_required(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings')
            ->put('/admin/settings', [
                'site_name' => '',
                'site_logo' => str_repeat('a', 2049),
            ])
            ->assertRedirect('/admin/settings')
            ->assertSessionHasErrors(['site_name', 'site_logo']);

        $this->assertSame(0, Setting::query()->count());
    }
}
