<?php

namespace Sitewyn\Packages\Blog\Http\Controllers\Public;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Models\Language;
use Sitewyn\Packages\Blog\Repositories\PostRepository;

class PostPublicController extends Controller
{
    public function __construct(
        private readonly PostRepository $posts,
    ) {}

    public function show(string $slug): View
    {
        $post = $this->posts->findPublishedBySlug($slug);

        if ($post === null) {
            abort(404);
        }

        // frontend.post resolves into the active theme (P5-02) — the theme
        // owns the public views, the package ships none.
        return view('frontend.post', [
            'post' => $post->load('tags'),
            'translation' => null,
        ]);
    }

    /**
     * Localized content view (P5-01): the slug stays the default language's
     * — translations never own slugs — and only title/content/SEO come from
     * the translation, falling back per field. Only active, non-default
     * locales resolve: the default language is already served by show(), and
     * unknown or inactive locales must 404.
     */
    public function showLocalized(string $locale, string $slug): View
    {
        $language = Language::findTranslatable($locale);

        if ($language === null) {
            abort(404);
        }

        $post = $this->posts->findPublishedBySlug($slug);

        if ($post === null) {
            abort(404);
        }

        return view('frontend.post', [
            'post' => $post->load('tags'),
            'translation' => $post->translations()->where('locale', $language->code)->first(),
        ]);
    }
}
