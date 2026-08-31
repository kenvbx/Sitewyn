<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Blog\Repositories\PostRepository;
use Sitewyn\Packages\Page\Models\Page;
use Sitewyn\Packages\Page\Repositories\PageRepository;

class HomeController extends Controller
{
    /**
     * Teasers show at most this many plain-text characters of the stored
     * rich HTML before an ellipsis.
     */
    private const EXCERPT_LENGTH = 160;

    public function __construct(
        private readonly PostRepository $posts,
        private readonly PageRepository $pages,
    ) {}

    /**
     * CMS front page: the latest published posts first, a compact page index
     * below. frontend.home resolves into the active theme (P5-02) — this is
     * cross-package (blog + page), which is why it lives in the app layer.
     */
    public function index(): View
    {
        $posts = $this->posts->byStatus(Post::STATUS_PUBLISHED);
        $posts->each(fn (Post $post) => $post->setAttribute('excerpt', $this->excerpt($post->content)));

        return view('frontend.home', [
            'posts' => $posts,
            'pages' => $this->pages->byStatus(Page::STATUS_PUBLISHED),
        ]);
    }

    /**
     * Plain-text teaser: the stored rich HTML is stripped, whitespace is
     * collapsed, and the result is capped (content is nullable).
     */
    private function excerpt(?string $content): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $content)));

        return Str::limit($text, self::EXCERPT_LENGTH);
    }
}
