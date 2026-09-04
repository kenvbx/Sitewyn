<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Models\Language;
use Tests\TestCase;

class AdminLocalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_routes_require_localization_locale_permission(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);
        $english = Language::query()->where('code', 'en')->firstOrFail();

        $this->actingAs($user, 'admin')
            ->get('/admin/translations/locales')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->post('/admin/translations/locales', ['locale' => 'vi'])
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get('/admin/translations/locales/'.$english->id.'/download')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->post('/admin/translations/locales/'.$english->id.'/delete')
            ->assertForbidden();
    }

    public function test_super_admin_can_view_locales_page(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'admin')
            ->get('/admin/translations/locales')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Settings')
            ->assertSee('Localization')
            ->assertSee('Locales')
            ->assertSee('Locale')
            ->assertSee('Select locale')
            ->assertSee('Add new locale')
            ->assertSee('Name')
            ->assertSee('Is default?')
            ->assertSee('Actions')
            ->assertSee('English')
            ->assertSee('Yes')
            ->assertSee('Tiếng Việt - vi')
            ->assertSee('data-admin-select2', false)
            ->assertSee('vendor/core-base/libraries/select2/css/select2.min.css', false)
            ->assertSee('vendor/core-base/libraries/select2/js/select2.full.min.js', false);
    }

    public function test_super_admin_can_add_locale_from_catalog(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'admin')
            ->post('/admin/translations/locales', ['locale' => 'vi'])
            ->assertRedirect('/admin/translations/locales')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('languages', [
            'code' => 'vi',
            'name' => 'Tiếng Việt',
            'locale' => 'vi',
            'is_default' => false,
            'is_active' => true,
        ]);
    }

    public function test_locale_must_be_unique(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'admin')
            ->from('/admin/translations/locales')
            ->post('/admin/translations/locales', ['locale' => 'en'])
            ->assertRedirect('/admin/translations/locales')
            ->assertSessionHasErrors('locale');
    }

    public function test_super_admin_can_download_locale_file(): void
    {
        $admin = $this->superAdmin();
        $english = Language::query()->where('code', 'en')->firstOrFail();

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/translations/locales/'.$english->id.'/download')
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=en.json');

        $this->assertStringContainsString('"locale": "en"', $response->streamedContent());
    }

    public function test_super_admin_can_delete_non_default_locale(): void
    {
        $admin = $this->superAdmin();
        $language = Language::query()->create([
            'code' => 'vi',
            'name' => 'Tiếng Việt',
            'locale' => 'vi',
            'flag' => 'vn',
            'text_direction' => 'ltr',
            'order' => 1,
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/translations/locales/'.$language->id.'/delete')
            ->assertRedirect('/admin/translations/locales')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('languages', ['code' => 'vi']);
    }

    public function test_default_locale_cannot_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $english = Language::query()->where('code', 'en')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post('/admin/translations/locales/'.$english->id.'/delete')
            ->assertRedirect('/admin/translations/locales')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('languages', [
            'code' => 'en',
            'is_default' => true,
        ]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
    }
}
