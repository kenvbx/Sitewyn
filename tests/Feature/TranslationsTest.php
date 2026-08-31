<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Models\Language;
use Sitewyn\Packages\Blog\Models\Category;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

/**
 * Content translation flows (P5-01): admin forms, persistence, and the
 * localized public routes with per-field fallback to the default language.
 */
class TranslationsTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Admin form UI ----------

    public function test_page_form_shows_add_languages_hint_when_only_the_default_exists(): void
    {
        $admin = $this->superAdmin();
        $page = $this->createPage();

        $this->actingAs($admin, 'admin')
            ->get('/admin/pages/'.$page->id.'/edit')
            ->assertOk()
            ->assertSee('Translations')
            ->assertSee('Add languages in Settings to translate content.');
    }

    public function test_page_form_renders_a_card_per_extra_language_with_default_placeholders(): void
    {
        $admin = $this->superAdmin();
        $this->addLanguage('vi', 'Vietnamese');
        $this->addLanguage('fr', 'French');
        $page = $this->createPage([
            'title' => 'About us',
            'content' => '<p>Default body</p>',
            'seo_title' => 'About Sitewyn',
            'seo_description' => 'Default description.',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/pages/'.$page->id.'/edit')
            ->assertOk()
            ->assertSee('Vietnamese (VI)')
            ->assertSee('French (FR)')
            ->assertSee('name="translations[vi][title]"', false)
            ->assertSee('name="translations[fr][content]"', false)
            ->assertSee('name="translations[vi][seo_title]"', false)
            ->assertSee('placeholder="About us"', false)
            ->assertSee('placeholder="About Sitewyn"', false)
            ->assertSee('placeholder="Default description."', false);
    }

    public function test_category_form_renders_name_field_per_language_only(): void
    {
        $admin = $this->superAdmin();
        $this->addLanguage('vi', 'Vietnamese');
        $category = $this->createCategory(['name' => 'Technology']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/categories/'.$category->id.'/edit')
            ->assertOk()
            ->assertSee('Vietnamese (VI)')
            ->assertSee('name="translations[vi][name]"', false)
            ->assertDontSee('name="translations[vi][content]"', false);
    }

    // ---------- Page translation persistence ----------

    public function test_page_store_persists_translation_rows(): void
    {
        $admin = $this->superAdmin();
        $this->addLanguage('vi', 'Vietnamese');

        $this->actingAs($admin, 'admin')
            ->post('/admin/pages', [
                'title' => 'About us',
                'status' => Page::STATUS_PUBLISHED,
                'content' => '<p>Default body</p>',
                'translations' => [
                    'vi' => [
                        'title' => 'Về chúng tôi',
                        'content' => '<p>Nội dung tiếng Việt</p>',
                        'seo_title' => 'Về Sitewyn',
                        'seo_description' => 'Mô tả tiếng Việt.',
                    ],
                ],
            ])
            ->assertRedirect('/admin/pages')
            ->assertSessionHas('status');

        $page = Page::query()->where('slug', 'about-us')->firstOrFail();

        $this->assertDatabaseHas('page_translations', [
            'page_id' => $page->id,
            'locale' => 'vi',
            'title' => 'Về chúng tôi',
            'content' => '<p>Nội dung tiếng Việt</p>',
            'seo_title' => 'Về Sitewyn',
            'seo_description' => 'Mô tả tiếng Việt.',
        ]);
    }

    public function test_page_update_edits_translation_without_duplicating_rows(): void
    {
        $admin = $this->superAdmin();
        $this->addLanguage('vi', 'Vietnamese');
        $page = $this->createPage(['title' => 'About us', 'status' => Page::STATUS_PUBLISHED]);

        $payload = [
            'title' => 'About us',
            'status' => Page::STATUS_PUBLISHED,
            'translations' => ['vi' => ['title' => 'Tiêu đề gốc']],
        ];

        $this->actingAs($admin, 'admin')
            ->put('/admin/pages/'.$page->id, $payload)
            ->assertRedirect('/admin/pages/'.$page->id.'/edit')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('page_translations', [
            'page_id' => $page->id,
            'locale' => 'vi',
            'title' => 'Tiêu đề gốc',
        ]);

        $payload['translations']['vi']['title'] = 'Tiêu đề mới';

        $this->actingAs($admin, 'admin')
            ->put('/admin/pages/'.$page->id, $payload)
            ->assertRedirect('/admin/pages/'.$page->id.'/edit');

        $this->assertSame(1, $page->translations()->where('locale', 'vi')->count());
        $this->assertDatabaseHas('page_translations', [
            'page_id' => $page->id,
            'locale' => 'vi',
            'title' => 'Tiêu đề mới',
        ]);
    }

    public function test_page_update_deletes_translation_when_every_field_is_empty(): void
    {
        $admin = $this->superAdmin();
        $this->addLanguage('vi', 'Vietnamese');
        $page = $this->createPage(['title' => 'About us', 'status' => Page::STATUS_PUBLISHED]);

        $page->translations()->create(['locale' => 'vi', 'title' => 'Về chúng tôi']);

        $this->actingAs($admin, 'admin')
            ->put('/admin/pages/'.$page->id, [
                'title' => 'About us',
                'status' => Page::STATUS_PUBLISHED,
                'translations' => ['vi' => ['title' => '', 'content' => '', 'seo_title' => '', 'seo_description' => '']],
            ])
            ->assertRedirect('/admin/pages/'.$page->id.'/edit');

        $this->assertDatabaseMissing('page_translations', [
            'page_id' => $page->id,
            'locale' => 'vi',
        ]);
    }

    public function test_page_store_rejects_unknown_and_default_locale_keys(): void
    {
        $admin = $this->superAdmin();
        $this->addLanguage('vi', 'Vietnamese');

        $this->actingAs($admin, 'admin')
            ->post('/admin/pages', [
                'title' => 'About us',
                'status' => Page::STATUS_DRAFT,
                'translations' => ['xx' => ['title' => 'Nope']],
            ])
            ->assertSessionHasErrors('translations');

        $this->actingAs($admin, 'admin')
            ->post('/admin/pages', [
                'title' => 'About us',
                'status' => Page::STATUS_DRAFT,
                'translations' => ['en' => ['title' => 'Nope']],
            ])
            ->assertSessionHasErrors('translations');

        $this->assertSame(0, Page::query()->count());
    }

    // ---------- Page frontend ----------

    public function test_localized_page_url_serves_the_translation(): void
    {
        $this->addLanguage('vi', 'Vietnamese');
        $page = $this->createPage([
            'title' => 'About us',
            'slug' => 'about-us',
            'content' => '<p>Default body</p>',
            'status' => Page::STATUS_PUBLISHED,
        ]);
        $page->translations()->create([
            'locale' => 'vi',
            'title' => 'Về chúng tôi',
            'content' => '<p>Nội dung tiếng Việt</p>',
        ]);

        $this->get('/vi/about-us')
            ->assertOk()
            ->assertSee('<html lang="vi">', false)
            ->assertSee('Về chúng tôi')
            ->assertSee('<p>Nội dung tiếng Việt</p>', false);

        // The default-language URL keeps serving the default language.
        $this->get('/about-us')
            ->assertOk()
            ->assertSee('About us')
            ->assertSee('<p>Default body</p>', false)
            ->assertDontSee('Về chúng tôi');
    }

    public function test_localized_page_falls_back_per_field_to_the_default_language(): void
    {
        $this->addLanguage('vi', 'Vietnamese');
        $page = $this->createPage([
            'title' => 'About us',
            'slug' => 'about-us',
            'content' => '<p>Default body</p>',
            'seo_description' => 'Default description.',
            'status' => Page::STATUS_PUBLISHED,
        ]);
        // Only the title is translated: content and SEO fall back.
        $page->translations()->create(['locale' => 'vi', 'title' => 'Về chúng tôi']);

        $this->get('/vi/about-us')
            ->assertOk()
            ->assertSee('Về chúng tôi')
            ->assertSee('<p>Default body</p>', false)
            ->assertSee('Default description.');
    }

    public function test_localized_page_urls_404_for_unknown_inactive_or_default_locales(): void
    {
        $this->addLanguage('vi', 'Vietnamese');
        $page = $this->createPage([
            'title' => 'About us',
            'slug' => 'about-us',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        $this->get('/xx/about-us')->assertNotFound();
        $this->get('/en/about-us')->assertNotFound();

        Language::query()->where('code', 'vi')->update(['is_active' => false]);
        $this->get('/vi/about-us')->assertNotFound();
    }

    public function test_localized_draft_page_is_not_found(): void
    {
        $this->addLanguage('vi', 'Vietnamese');
        $page = $this->createPage(['title' => 'Coming soon', 'slug' => 'coming-soon']);
        $page->translations()->create(['locale' => 'vi', 'title' => 'Sắp ra mắt']);

        $this->get('/vi/coming-soon')->assertNotFound();
        $this->get('/coming-soon')->assertNotFound();
    }

    // ---------- Post translation persistence + frontend ----------

    public function test_post_store_persists_translation_and_update_does_not_duplicate(): void
    {
        $admin = $this->superAdmin();
        $this->addLanguage('vi', 'Vietnamese');

        $this->actingAs($admin, 'admin')
            ->post('/admin/posts', [
                'title' => 'Hello world',
                'status' => Post::STATUS_PUBLISHED,
                'content' => '<p>Default post body</p>',
                'translations' => ['vi' => ['title' => 'Xin chào', 'content' => '<p>Nội dung bài viết</p>']],
            ])
            ->assertRedirect('/admin/posts')
            ->assertSessionHas('status');

        $post = Post::query()->where('slug', 'hello-world')->firstOrFail();

        $this->assertDatabaseHas('post_translations', [
            'post_id' => $post->id,
            'locale' => 'vi',
            'title' => 'Xin chào',
            'content' => '<p>Nội dung bài viết</p>',
        ]);

        $this->actingAs($admin, 'admin')
            ->put('/admin/posts/'.$post->id, [
                'title' => 'Hello world',
                'status' => Post::STATUS_PUBLISHED,
                'translations' => ['vi' => ['title' => 'Xin chào thế giới']],
            ])
            ->assertRedirect('/admin/posts/'.$post->id.'/edit');

        $this->assertSame(1, $post->translations()->where('locale', 'vi')->count());
        $this->assertDatabaseHas('post_translations', [
            'post_id' => $post->id,
            'locale' => 'vi',
            'title' => 'Xin chào thế giới',
        ]);
    }

    public function test_localized_post_url_serves_the_translation_with_fallback(): void
    {
        $this->addLanguage('vi', 'Vietnamese');
        $post = Post::query()->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
            'content' => '<p>Default post body</p>',
            'status' => Post::STATUS_PUBLISHED,
        ]);
        $post->translations()->create(['locale' => 'vi', 'title' => 'Xin chào']);

        $this->get('/vi/blog/hello-world')
            ->assertOk()
            ->assertSee('<html lang="vi">', false)
            ->assertSee('Xin chào')
            // Content is untranslated, so it falls back to the default post.
            ->assertSee('<p>Default post body</p>', false);

        $this->get('/blog/hello-world')
            ->assertOk()
            ->assertSee('Hello world')
            ->assertDontSee('Xin chào');
    }

    public function test_localized_post_urls_404_for_unknown_locales_and_drafts(): void
    {
        $this->addLanguage('vi', 'Vietnamese');
        $draft = Post::query()->create([
            'title' => 'Draft post',
            'slug' => 'draft-post',
            'status' => Post::STATUS_DRAFT,
        ]);
        $draft->translations()->create(['locale' => 'vi', 'title' => 'Bản nháp']);

        $published = Post::query()->create([
            'title' => 'Published post',
            'slug' => 'published-post',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $this->get('/xx/blog/published-post')->assertNotFound();
        $this->get('/en/blog/published-post')->assertNotFound();
        $this->get('/vi/blog/draft-post')->assertNotFound();
    }

    // ---------- Category translation persistence ----------

    public function test_category_store_and_update_persist_name_translation_without_duplicates(): void
    {
        $admin = $this->superAdmin();
        $this->addLanguage('vi', 'Vietnamese');

        $this->actingAs($admin, 'admin')
            ->post('/admin/categories', [
                'name' => 'Technology',
                'translations' => ['vi' => ['name' => 'Công nghệ']],
            ])
            ->assertRedirect('/admin/categories')
            ->assertSessionHas('status');

        $category = Category::query()->where('slug', 'technology')->firstOrFail();

        $this->assertDatabaseHas('category_translations', [
            'category_id' => $category->id,
            'locale' => 'vi',
            'name' => 'Công nghệ',
        ]);

        $this->actingAs($admin, 'admin')
            ->put('/admin/categories/'.$category->id, [
                'name' => 'Technology',
                'translations' => ['vi' => ['name' => 'Công nghệ mới']],
            ])
            ->assertRedirect('/admin/categories/'.$category->id.'/edit');

        $this->assertSame(1, $category->translations()->where('locale', 'vi')->count());
        $this->assertDatabaseHas('category_translations', [
            'category_id' => $category->id,
            'locale' => 'vi',
            'name' => 'Công nghệ mới',
        ]);
    }

    public function test_category_update_deletes_translation_when_name_is_empty(): void
    {
        $admin = $this->superAdmin();
        $this->addLanguage('vi', 'Vietnamese');
        $category = $this->createCategory(['name' => 'Technology']);
        $category->translations()->create(['locale' => 'vi', 'name' => 'Công nghệ']);

        $this->actingAs($admin, 'admin')
            ->put('/admin/categories/'.$category->id, [
                'name' => 'Technology',
                'translations' => ['vi' => ['name' => '']],
            ])
            ->assertRedirect('/admin/categories/'.$category->id.'/edit');

        $this->assertDatabaseMissing('category_translations', [
            'category_id' => $category->id,
            'locale' => 'vi',
        ]);
    }

    // ---------- Helpers ----------

    private function addLanguage(string $code, string $name): Language
    {
        return Language::query()->create([
            'code' => $code,
            'name' => $name,
            'is_default' => false,
            'is_active' => true,
        ]);
    }

    private function createPage(array $attributes = []): Page
    {
        return Page::query()->create([
            'title' => 'Untitled',
            'slug' => 'untitled-'.uniqid(),
            'status' => Page::STATUS_DRAFT,
            ...$attributes,
        ]);
    }

    private function createCategory(array $attributes = []): Category
    {
        return Category::query()->create([
            'name' => 'Uncategorized',
            'slug' => 'uncategorized-'.uniqid(),
            ...$attributes,
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
