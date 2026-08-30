<?php

namespace Sitewyn\Packages\Blog\Http\Controllers\Public;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
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

        return view('package/blog::frontend.show', [
            'post' => $post->load('tags'),
        ]);
    }
}
