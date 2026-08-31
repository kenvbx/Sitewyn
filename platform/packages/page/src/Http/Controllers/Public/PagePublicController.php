<?php

namespace Sitewyn\Packages\Page\Http\Controllers\Public;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Models\Language;
use Sitewyn\Packages\Page\Repositories\PageRepository;

class PagePublicController extends Controller
{
    public function __construct(
        private readonly PageRepository $pages,
    ) {}

    public function show(string $slug): View
    {
        $page = $this->pages->findPublishedBySlug($slug);

        if ($page === null) {
            abort(404);
        }

        // frontend.page resolves into the active theme (P5-02) — the theme
        // owns the public views, the package ships none.
        return view('frontend.page', [
            'page' => $page,
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

        $page = $this->pages->findPublishedBySlug($slug);

        if ($page === null) {
            abort(404);
        }

        return view('frontend.page', [
            'page' => $page,
            'translation' => $page->translations()->where('locale', $language->code)->first(),
        ]);
    }
}
