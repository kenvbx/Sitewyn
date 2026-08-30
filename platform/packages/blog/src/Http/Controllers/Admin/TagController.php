<?php

namespace Sitewyn\Packages\Blog\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Support\SlugService;
use Sitewyn\Packages\Blog\Http\Requests\Admin\StoreTagRequest;
use Sitewyn\Packages\Blog\Http\Requests\Admin\UpdateTagRequest;
use Sitewyn\Packages\Blog\Models\Tag;
use Sitewyn\Packages\Blog\Repositories\TagRepository;

class TagController extends Controller
{
    public function __construct(
        private readonly TagRepository $tags,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $tags = $search === ''
            ? $this->tags->all()
            : $this->tags->searchByName($search);

        return view('package/blog::admin.tags.index', [
            'tags' => $tags,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('package/blog::admin.tags.create', [
            'tag' => new Tag,
        ]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        $attributes = $request->validated();

        // Tag slugs live in their own namespace: uniqueness is only checked
        // against the tags table, never pages/posts/categories.
        $attributes['slug'] = $this->uniqueSlug(
            (string) ($attributes['slug'] ?? ''),
            (string) $attributes['name'],
        );

        $this->tags->create($attributes);

        admin_flash()->success(__('Tag created successfully.'));

        return redirect()
            ->route('admin.tags.index');
    }

    public function edit(Tag $tag): View
    {
        return view('package/blog::admin.tags.edit', [
            'tag' => $tag,
        ]);
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $attributes = $request->validated();

        // An empty slug keeps the current one; the repository only
        // regenerates slugs when a slug key is actually present.
        if ((string) ($attributes['slug'] ?? '') === '') {
            unset($attributes['slug']);
        } else {
            $attributes['slug'] = (new SlugService)->uniqueFor(
                (string) $attributes['slug'],
                ['tags'],
                $tag->id,
                'tags',
            );
        }

        $this->tags->update($tag, $attributes);

        admin_flash()->success(__('Tag updated successfully.'));

        return redirect()
            ->route('admin.tags.edit', $tag);
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $this->tags->delete($tag);
        admin_flash()->success(__('Tag deleted successfully.'));

        return redirect()
            ->route('admin.tags.index');
    }

    private function uniqueSlug(string $slug, string $name): string
    {
        $service = new SlugService;

        return $slug !== ''
            ? $service->uniqueFor($slug, ['tags'])
            : $service->generateUnique($name, ['tags']);
    }
}
