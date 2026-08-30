<?php

namespace Sitewyn\Packages\Blog\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Support\SlugService;
use Sitewyn\Packages\Blog\Http\Requests\Admin\StoreCategoryRequest;
use Sitewyn\Packages\Blog\Http\Requests\Admin\UpdateCategoryRequest;
use Sitewyn\Packages\Blog\Models\Category;
use Sitewyn\Packages\Blog\Repositories\CategoryRepository;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryRepository $categories,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $categories = $search === ''
            ? $this->categories->all()
            : $this->categories->searchByName($search);

        return view('package/blog::admin.categories.index', [
            'categories' => $categories,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('package/blog::admin.categories.create', [
            'category' => new Category,
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $attributes = $request->validated();

        // Category slugs live in their own namespace: uniqueness is only
        // checked against the categories table, never pages/posts.
        $attributes['slug'] = $this->uniqueSlug(
            (string) ($attributes['slug'] ?? ''),
            (string) $attributes['name'],
        );

        $this->categories->create($attributes);

        admin_flash()->success(__('Category created successfully.'));

        return redirect()
            ->route('admin.categories.index');
    }

    public function edit(Category $category): View
    {
        return view('package/blog::admin.categories.edit', [
            'category' => $category,
            'parents' => $this->parentOptions($category),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $attributes = $request->validated();

        // An empty slug keeps the current one; the repository only
        // regenerates slugs when a slug key is actually present.
        if ((string) ($attributes['slug'] ?? '') === '') {
            unset($attributes['slug']);
        } else {
            $attributes['slug'] = (new SlugService)->uniqueFor(
                (string) $attributes['slug'],
                ['categories'],
                $category->id,
                'categories',
            );
        }

        $this->categories->update($category, $attributes);

        admin_flash()->success(__('Category updated successfully.'));

        return redirect()
            ->route('admin.categories.edit', $category);
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->categories->delete($category);
        admin_flash()->success(__('Category deleted successfully.'));

        return redirect()
            ->route('admin.categories.index');
    }

    /**
     * Parent choices for the form. When editing, the category itself and its
     * whole subtree are excluded so the select can never express a cycle.
     *
     * @return array<string, string>
     */
    private function parentOptions(?Category $category = null): array
    {
        $excludedIds = $category === null
            ? collect()
            : $category->descendants()->pluck('id')->push($category->id);

        return ['' => '— None —']
            + $this->categories->all()
                ->reject(fn (Category $item): bool => $excludedIds->contains((int) $item->id))
                ->mapWithKeys(fn (Category $item): array => [(string) $item->id => $item->name])
                ->all();
    }

    private function uniqueSlug(string $slug, string $name): string
    {
        $service = new SlugService;

        return $slug !== ''
            ? $service->uniqueFor($slug, ['categories'])
            : $service->generateUnique($name, ['categories']);
    }
}
