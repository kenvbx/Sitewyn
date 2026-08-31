<?php

namespace Sitewyn\Packages\Blog\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Models\Language;
use Sitewyn\Core\Base\Support\SlugService;
use Sitewyn\Core\Base\Support\Translations;
use Sitewyn\Packages\Blog\Http\Requests\Admin\StorePostRequest;
use Sitewyn\Packages\Blog\Http\Requests\Admin\UpdatePostRequest;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Blog\Repositories\CategoryRepository;
use Sitewyn\Packages\Blog\Repositories\PostRepository;
use Sitewyn\Packages\Blog\Repositories\TagRepository;

class PostController extends Controller
{
    public function __construct(
        private readonly PostRepository $posts,
        private readonly CategoryRepository $categories,
        private readonly TagRepository $tags,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = $this->statusFilter($request->query('status'));
        $categoryId = $this->categoryFilter($request->query('category_id'));

        $posts = $search === '' && $status === null && $categoryId === null
            ? $this->posts->all()
            : $this->posts->search($search, $status, $categoryId);

        return view('package/blog::admin.index', [
            'posts' => $posts,
            'search' => $search,
            'status' => $status,
            'categoryId' => $categoryId,
            'categories' => $this->categories->all(),
        ]);
    }

    public function create(): View
    {
        return view('package/blog::admin.create', [
            'post' => new Post,
            'categories' => $this->categories->all(),
            'languages' => Language::translatable(),
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $attributes = $request->validated();
        $translations = $attributes['translations'] ?? null;
        unset($attributes['translations']);

        $tagsInput = (string) ($attributes['tags_input'] ?? '');
        unset($attributes['tags_input']);

        $post = $this->posts->create($attributes);
        $this->syncTags($post, $tagsInput);
        $this->saveTranslations($post, $translations);

        admin_flash()->success(__('Post created successfully.'));

        return redirect()
            ->route('admin.posts.index');
    }

    public function edit(Post $post): View
    {
        return view('package/blog::admin.edit', [
            'post' => $post,
            'categories' => $this->categories->all(),
            'languages' => Language::translatable(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $attributes = $request->validated();
        $translations = $attributes['translations'] ?? null;
        unset($attributes['translations']);

        $tagsInput = (string) ($attributes['tags_input'] ?? '');
        unset($attributes['tags_input']);

        // An empty slug keeps the current one; the repository only
        // regenerates slugs when a slug key is actually present.
        if ((string) ($attributes['slug'] ?? '') === '') {
            unset($attributes['slug']);
        }

        $this->posts->update($post, $attributes);
        $this->syncTags($post, $tagsInput);
        $this->saveTranslations($post, $translations);

        admin_flash()->success(__('Post updated successfully.'));

        return redirect()
            ->route('admin.posts.edit', $post);
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->posts->delete($post);
        admin_flash()->success(__('Post deleted successfully.'));

        return redirect()
            ->route('admin.posts.index');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = collect((array) $request->input('ids', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            admin_flash()->warning(__('Select at least one post to delete.'));

            return redirect()
                ->route('admin.posts.index');
        }

        $posts = Post::query()->whereIn('id', $ids)->get();

        foreach ($posts as $post) {
            $this->posts->delete($post);
        }

        admin_flash()->success(__('Deleted :count posts.', ['count' => $posts->count()]));

        return redirect()
            ->route('admin.posts.index');
    }

    public function preview(Post $post): View
    {
        return view('package/blog::preview', [
            'post' => $post->load(['category', 'tags']),
        ]);
    }

    /**
     * Parse the comma-separated tag names, reuse existing tags by name and
     * create the missing ones, then sync the result onto the post. An update
     * always re-syncs from scratch, so removed names detach.
     */
    private function syncTags(Post $post, string $tagsInput): void
    {
        $names = collect(explode(',', $tagsInput))
            ->map(fn (string $name): string => trim($name))
            ->filter(fn (string $name): bool => $name !== '')
            ->unique(fn (string $name): string => mb_strtolower($name))
            ->values();

        $tagIds = [];

        foreach ($names as $name) {
            $tag = $this->tags->findByName($name);

            if ($tag === null) {
                $tag = $this->tags->create([
                    'name' => $name,
                    // Tag slugs live in their own namespace: uniqueness is only
                    // checked against the tags table, never pages/posts.
                    'slug' => (new SlugService)->generateUnique($name, ['tags']),
                ]);
            }

            $tagIds[] = $tag->id;
        }

        $post->tags()->sync($tagIds);
    }

    private function statusFilter(mixed $status): ?string
    {
        $status = is_string($status) ? trim($status) : '';

        return in_array($status, [Post::STATUS_DRAFT, Post::STATUS_PUBLISHED], true) ? $status : null;
    }

    /**
     * Upsert or remove one translation row per submitted locale. Kept here
     * (not in the repository) so the shared Translations helper receives the
     * relation and the post columns stay repository-owned.
     *
     * @param  array<string, mixed>|null  $translations
     */
    private function saveTranslations(Post $post, ?array $translations): void
    {
        Translations::save($post->translations(), $translations, [
            'title',
            'content',
            'seo_title',
            'seo_description',
        ]);
    }

    private function categoryFilter(mixed $categoryId): ?int
    {
        if (! is_numeric($categoryId)) {
            return null;
        }

        $categoryId = (int) $categoryId;

        return $categoryId > 0 ? $categoryId : null;
    }
}
