<?php

namespace Sitewyn\Packages\Page\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
        ]);
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $this->pages->create($request->validated());
        admin_flash()->success(__('Page created successfully.'));

        return redirect()
            ->route('admin.pages.index');
    }

    public function edit(Page $page): View
    {
        return view('package/page::admin.edit', [
            'page' => $page,
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $attributes = $request->validated();

        // An empty slug keeps the current one; the repository only
        // regenerates slugs when a slug key is actually present.
        if ((string) ($attributes['slug'] ?? '') === '') {
            unset($attributes['slug']);
        }

        $this->pages->update($page, $attributes);
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
}
