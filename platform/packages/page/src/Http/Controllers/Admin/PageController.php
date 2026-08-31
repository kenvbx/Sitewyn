<?php

namespace Sitewyn\Packages\Page\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Models\Language;
use Sitewyn\Core\Base\Support\Translations;
use Sitewyn\Packages\Page\Http\Requests\Admin\StorePageRequest;
use Sitewyn\Packages\Page\Http\Requests\Admin\UpdatePageRequest;
use Sitewyn\Packages\Page\Models\Page;
use Sitewyn\Packages\Page\Repositories\PageRepository;

class PageController extends Controller
{
    public function __construct(
        private readonly PageRepository $pages,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = $this->statusFilter($request->query('status'));

        $pages = $search === '' && $status === null
            ? $this->pages->all()
            : $this->pages->search($search, $status);

        return view('package/page::admin.index', [
            'pages' => $pages,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('package/page::admin.create', [
            'page' => new Page,
            'languages' => Language::translatable(),
        ]);
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $attributes = $request->validated();
        $translations = $attributes['translations'] ?? null;
        unset($attributes['translations']);

        $page = $this->pages->create($attributes);
        $this->saveTranslations($page, $translations);

        admin_flash()->success(__('Page created successfully.'));

        return redirect()
            ->route('admin.pages.index');
    }

    public function edit(Page $page): View
    {
        return view('package/page::admin.edit', [
            'page' => $page,
            'languages' => Language::translatable(),
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $attributes = $request->validated();
        $translations = $attributes['translations'] ?? null;
        unset($attributes['translations']);

        // An empty slug keeps the current one; the repository only
        // regenerates slugs when a slug key is actually present.
        if ((string) ($attributes['slug'] ?? '') === '') {
            unset($attributes['slug']);
        }

        $this->pages->update($page, $attributes);
        $this->saveTranslations($page, $translations);

        admin_flash()->success(__('Page updated successfully.'));

        return redirect()
            ->route('admin.pages.edit', $page);
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->pages->delete($page);
        admin_flash()->success(__('Page deleted successfully.'));

        return redirect()
            ->route('admin.pages.index');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = collect((array) $request->input('ids', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            admin_flash()->warning(__('Select at least one page to delete.'));

            return redirect()
                ->route('admin.pages.index');
        }

        $pages = Page::query()->whereIn('id', $ids)->get();

        foreach ($pages as $page) {
            $this->pages->delete($page);
        }

        admin_flash()->success(__('Deleted :count pages.', ['count' => $pages->count()]));

        return redirect()
            ->route('admin.pages.index');
    }

    public function preview(Page $page): View
    {
        return view('package/page::preview', [
            'page' => $page,
        ]);
    }

    private function statusFilter(mixed $status): ?string
    {
        $status = is_string($status) ? trim($status) : '';

        return in_array($status, [Page::STATUS_DRAFT, Page::STATUS_PUBLISHED], true) ? $status : null;
    }

    /**
     * Upsert or remove one translation row per submitted locale. Kept here
     * (not in the repository) so the shared Translations helper receives the
     * relation and the page columns stay repository-owned.
     *
     * @param  array<string, mixed>|null  $translations
     */
    private function saveTranslations(Page $page, ?array $translations): void
    {
        Translations::save($page->translations(), $translations, [
            'title',
            'content',
            'seo_title',
            'seo_description',
        ]);
    }
}
