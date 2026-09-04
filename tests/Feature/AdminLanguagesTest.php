<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Models\Language;
use Sitewyn\Core\Base\Support\LanguageCatalog;
use Sitewyn\Packages\Page\Models\Page;
use Sitewyn\Packages\Page\Models\PageTranslation;
use Tests\TestCase;

class AdminLanguagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_seeds_english_as_the_single_default_language(): void
    {
        $this->assertSame(1, Language::query()->count());

        $this->assertDatabaseHas('languages', [
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_language_routes_require_the_settings_edit_permission(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);
        $english = Language::query()->where('code', 'en')->firstOrFail();

        $this->actingAs($user, 'admin')
            ->get('/admin/settings/languages')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->post('/admin/settings/languages', ['code' => 'vi', 'name' => 'Vietnamese'])
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->put('/admin/settings/languages/settings', [
                'language_display' => 'all',
                'language_switcher_display' => 'dropdown',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->post('/admin/settings/languages/'.$english->id.'/delete')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->put('/admin/settings/languages/'.$english->id, [
                'code' => 'en',
                'name' => 'English',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->post('/admin/settings/languages/'.$english->id.'/make-default')
            ->assertForbidden();
    }

    public function test_super_admin_can_view_languages_page_with_english_default(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/languages')
            ->assertOk()
            ->assertSee('English')
            ->assertSee('Default')
            ->assertSee('Detail')
            ->assertSee('Settings')
            ->assertSee('Choose a language')
            ->assertSee('Language name')
            ->assertSee('Locale')
            ->assertSee('Language code')
            ->assertSee('Text direction')
            ->assertSee('Flag')
            ->assertSee('Order')
            ->assertSee('Is default?')
            ->assertSee('Actions')
            ->assertSee('Add new language')
            ->assertSee('Japanese')
            ->assertSee('Chinese - 中文')
            ->assertSee('data-admin-select2', false)
            ->assertSee('vendor/core-base/libraries/select2/css/select2.min.css', false)
            ->assertSee('vendor/core-base/libraries/select2/js/select2.full.min.js', false)
            ->assertSee('id="language-preset" class="form-select sitewyn-select2"', false)
            ->assertSee('id="language-locale" class="form-select sitewyn-select2"', false)
            ->assertSee('id="language-code" class="form-select sitewyn-select2"', false)
            ->assertSee('id="language-flag" class="form-select sitewyn-select2"', false)
            ->assertSee('name="code"', false)
            ->assertSee('name="locale"', false)
            ->assertSee('name="text_direction"', false)
            ->assertSee('name="flag"', false);
    }

    public function test_super_admin_can_add_a_language(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'admin')
            ->post('/admin/settings/languages', ['code' => 'vi', 'name' => 'Vietnamese'])
            ->assertRedirect('/admin/settings/languages')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('languages', [
            'code' => 'vi',
            'name' => 'Vietnamese',
            'is_default' => false,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_add_a_language_with_detail_fields(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'admin')
            ->post('/admin/settings/languages', [
                'code' => 'ar',
                'name' => 'Arabic',
                'locale' => 'ar',
                'flag' => 'sa',
                'text_direction' => 'rtl',
                'order' => 5,
            ])
            ->assertRedirect('/admin/settings/languages')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('languages', [
            'code' => 'ar',
            'name' => 'Arabic',
            'locale' => 'ar',
            'flag' => 'sa',
            'text_direction' => 'rtl',
            'order' => 5,
            'is_default' => false,
            'is_active' => true,
        ]);
    }

    public function test_language_catalog_combines_intl_languages_with_cms_defaults(): void
    {
        $catalog = app(LanguageCatalog::class);

        $this->assertArrayHasKey('ja', $catalog->languageOptions());
        $this->assertArrayHasKey('zh_CN', $catalog->localeOptions());
        $this->assertArrayHasKey('jp', $catalog->flagOptions());
        $this->assertSame([
            'locale' => 'vi',
            'flag' => 'vn',
            'text_direction' => 'ltr',
        ], $catalog->defaultsFor('vi'));
        $this->assertSame([
            'locale' => 'ar',
            'flag' => 'sa',
            'text_direction' => 'rtl',
        ], $catalog->defaultsFor('ar'));
    }

    public function test_super_admin_can_add_a_language_from_the_intl_catalog(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'admin')
            ->post('/admin/settings/languages', [
                'code' => 'ja',
                'name' => '日本語',
            ])
            ->assertRedirect('/admin/settings/languages')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('languages', [
            'code' => 'ja',
            'name' => '日本語',
            'locale' => 'ja',
            'flag' => 'jp',
            'text_direction' => 'ltr',
        ]);
    }

    public function test_super_admin_can_update_a_language(): void
    {
        $admin = $this->superAdmin();

        $language = Language::query()->create([
            'code' => 'vi',
            'name' => 'Vietnamese',
            'locale' => 'vi',
            'flag' => 'vn',
            'text_direction' => 'ltr',
            'order' => 1,
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->put('/admin/settings/languages/'.$language->id, [
                'code' => 'vi',
                'name' => 'Tiếng Việt',
                'locale' => 'vi',
                'flag' => 'vn',
                'text_direction' => 'ltr',
                'order' => 2,
            ])
            ->assertRedirect('/admin/settings/languages')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('languages', [
            'id' => $language->id,
            'code' => 'vi',
            'name' => 'Tiếng Việt',
            'locale' => 'vi',
            'flag' => 'vn',
            'text_direction' => 'ltr',
            'order' => 2,
        ]);
    }

    public function test_super_admin_can_update_language_settings(): void
    {
        $admin = $this->superAdmin();

        Language::query()->create([
            'code' => 'vi',
            'name' => 'Vietnamese',
            'locale' => 'vi',
            'flag' => 'vn',
            'text_direction' => 'ltr',
            'order' => 1,
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->put('/admin/settings/languages/settings', [
                'language_hide_default_from_url' => '1',
                'language_display' => 'flag',
                'language_switcher_display' => 'list',
                'language_hidden_codes' => ['en', 'vi'],
                'language_auto_detect' => '1',
            ])
            ->assertRedirect('/admin/settings/languages?tab=settings')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', [
            'key' => 'language_hide_default_from_url',
            'value' => '1',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'language_display',
            'value' => 'flag',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'language_switcher_display',
            'value' => 'list',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'language_hidden_codes',
            'value' => json_encode(['vi']),
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'language_auto_detect',
            'value' => '1',
        ]);
    }

    public function test_add_language_rejects_malformed_or_duplicate_codes(): void
    {
        $admin = $this->superAdmin();

        foreach (['V', 'abc', 'e1', 'en'] as $code) {
            $this->actingAs($admin, 'admin')
                ->post('/admin/settings/languages', ['code' => $code, 'name' => 'Bad'])
                ->assertSessionHasErrors('code');
        }

        $this->assertSame(1, Language::query()->count());
    }

    public function test_default_language_cannot_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $english = Language::query()->where('code', 'en')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post('/admin/settings/languages/'.$english->id.'/delete')
            ->assertRedirect('/admin/settings/languages');

        $this->assertDatabaseHas('languages', ['code' => 'en', 'is_default' => true]);
    }

    public function test_deleting_a_language_cascades_its_translations(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'admin')
            ->post('/admin/settings/languages', ['code' => 'vi', 'name' => 'Vietnamese'])
            ->assertRedirect('/admin/settings/languages');

        $page = Page::query()->create([
            'title' => 'About us',
            'slug' => 'about-us',
            'status' => Page::STATUS_PUBLISHED,
        ]);
        PageTranslation::query()->create([
            'page_id' => $page->id,
            'locale' => 'vi',
            'title' => 'Về chúng tôi',
        ]);

        $vi = Language::query()->where('code', 'vi')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post('/admin/settings/languages/'.$vi->id.'/delete')
            ->assertRedirect('/admin/settings/languages')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('languages', ['code' => 'vi']);
        $this->assertDatabaseMissing('page_translations', ['locale' => 'vi']);
    }

    public function test_make_default_promotes_language_and_demotes_the_old_default(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'admin')
            ->post('/admin/settings/languages', ['code' => 'vi', 'name' => 'Vietnamese'])
            ->assertRedirect('/admin/settings/languages');

        $vi = Language::query()->where('code', 'vi')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post('/admin/settings/languages/'.$vi->id.'/make-default')
            ->assertRedirect('/admin/settings/languages')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('languages', [
            'code' => 'vi',
            'is_default' => true,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('languages', ['code' => 'en', 'is_default' => false]);
    }

    public function test_settings_hub_links_to_language_management(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Languages')
            ->assertSee('View and update your website languages')
            ->assertSee(route('admin.settings.languages.index', [], false), false);
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
    }
}
