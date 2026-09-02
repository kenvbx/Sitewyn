<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Models\Language;
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
            ->post('/admin/settings/languages/'.$english->id.'/delete')
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
            ->assertSee('Add language')
            ->assertSee('name="code"', false);
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
