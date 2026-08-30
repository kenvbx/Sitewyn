<?php

namespace Sitewyn\Packages\Page\Http\Controllers\Public;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
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

        return view('package/page::frontend.show', [
            'page' => $page,
        ]);
    }
}
