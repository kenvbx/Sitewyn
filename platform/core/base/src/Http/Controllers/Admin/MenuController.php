<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Sitewyn\Core\Base\Http\Requests\Admin\StoreMenuItemsRequest;
use Sitewyn\Core\Base\Http\Requests\Admin\StoreMenuRequest;
use Sitewyn\Core\Base\Models\Menu;
use Sitewyn\Core\Base\Models\MenuItem;
use Sitewyn\Core\Base\Support\SlugService;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Blog\Repositories\PostRepository;
use Sitewyn\Packages\Page\Models\Page;
use Sitewyn\Packages\Page\Repositories\PageRepository;

/**
 * Frontend navigation menus (P5-03). One permission (menus.manage) gates the
 * whole CRUD plus the drag-and-drop builder — managing menus is a single
 * editing activity, not separate view/create/delete rights.
 */
class MenuController extends Controller
{
    public function index(): View
    {
        return view('core/base::admin.menus.index', [
            'menus' => Menu::query()
                ->withCount('items')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('core/base::admin.menus.create', [
            'menu' => new Menu,
        ]);
    }

    public function store(StoreMenuRequest $request): RedirectResponse
    {
        $attributes = $request->validated();

        $menu = DB::transaction(function () use ($attributes): Menu {
            $menu = Menu::query()->create([
                ...$attributes,
                'slug' => $this->uniqueSlug((string) ($attributes['slug'] ?? ''), (string) $attributes['name']),
            ]);

            return $this->claimLocation($menu);
        });

        admin_flash()->success(__('Menu created successfully. Add some items below.'));

        // The whole point of a new menu is its items — land in the builder.
        return redirect()->route('admin.menus.edit-items', $menu);
    }

    public function edit(Menu $menu): View
    {
        return view('core/base::admin.menus.edit', [
            'menu' => $menu,
        ]);
    }

    public function update(StoreMenuRequest $request, Menu $menu): RedirectResponse
    {
        $attributes = $request->validated();

        DB::transaction(function () use ($menu, $attributes): void {
            // An empty slug keeps the current one (pages/categories pattern).
            $slug = (string) ($attributes['slug'] ?? '');

            if ($slug !== '') {
                $slug = $this->uniqueSlug($slug, (string) $attributes['name'], $menu->id);
            }

            $menu->update([
                ...$attributes,
                'slug' => $slug !== '' ? $slug : $menu->slug,
            ]);

            $this->claimLocation($menu);
        });

        admin_flash()->success(__('Menu updated successfully.'));

        return redirect()->route('admin.menus.index');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        // Items go with the menu through the FK cascade — nothing else
        // references a menu or menu item id.
        $menu->delete();

        admin_flash()->success(__('Menu deleted successfully.'));

        return redirect()->route('admin.menus.index');
    }

    /**
     * The drag-and-drop builder: published pages/posts to pull from on the
     * left, the menu structure on the right. Reading the page/blog modules
     * here is the same accepted coupling the default theme's nav already
     * has — menus link to content, content cannot exist without it.
     */
    public function editItems(Menu $menu): View
    {
        $pages = app(PageRepository::class)->byStatus(Page::STATUS_PUBLISHED);
        $posts = app(PostRepository::class)->byStatus(Post::STATUS_PUBLISHED);

        // After a failed save the builder re-renders from the old input so
        // the editor does not lose their arrangement; otherwise from the DB.
        $oldItems = old('items');
        $rows = is_array($oldItems) && $oldItems !== []
            ? $this->rowsFromOldInput($oldItems)
            : $this->rowsFromMenu($menu->load('items.children'));

        return view('core/base::admin.menus.builder', [
            'menu' => $menu,
            'pages' => $pages,
            'posts' => $posts,
            'rows' => $rows,
            // Title hints for Page/Post rows; targets that were unpublished
            // or deleted since the item was added simply show no hint.
            'pageTitles' => $pages->pluck('title', 'id'),
            'postTitles' => $posts->pluck('title', 'id'),
        ]);
    }

    /**
     * Replace-all save: the builder always sends the full structure, so the
     * old rows are wiped and every row is re-created with a fresh id
     * (nothing references menu item ids). parent_id values from the payload
     * are request-scoped ids that get remapped onto the new rows.
     */
    public function storeItems(StoreMenuItemsRequest $request, Menu $menu): RedirectResponse
    {
        $rows = $request->items();

        DB::transaction(function () use ($menu, $rows): void {
            $menu->items()->delete();

            $idMap = [];

            foreach ($rows as $order => $row) {
                $item = MenuItem::query()->create([
                    'menu_id' => $menu->id,
                    'parent_id' => null,
                    'label' => $row['label'],
                    'type' => $row['type'],
                    'target_id' => $row['target_id'],
                    'url' => $row['url'],
                    'order' => $order,
                ]);

                $idMap[$row['id']] = $item->id;
            }

            foreach ($rows as $row) {
                if ($row['parent_id'] !== '' && isset($idMap[$row['parent_id']])) {
                    MenuItem::query()
                        ->whereKey($idMap[$row['id']])
                        ->update(['parent_id' => $idMap[$row['parent_id']]]);
                }
            }
        });

        admin_flash()->success(__('Menu structure saved.'));

        return redirect()->route('admin.menus.edit-items', $menu);
    }

    /**
     * One menu per location: assigning a location takes it away from any
     * other menu currently holding it (the default-language pattern).
     */
    private function claimLocation(Menu $menu): Menu
    {
        if ($menu->location === null) {
            return $menu;
        }

        Menu::query()
            ->whereKeyNot($menu->id)
            ->where('location', $menu->location)
            ->update(['location' => null]);

        return $menu->refresh();
    }

    private function uniqueSlug(string $slug, string $name, ?int $ignoreId = null): string
    {
        $service = new SlugService;

        return $slug !== ''
            ? $service->uniqueFor($slug, ['menus'], $ignoreId, 'menus')
            : $service->generateUnique($name, ['menus'], $ignoreId, 'menus');
    }

    /**
     * Flat builder rows (depth 0/1) from the stored menu, children directly
     * following their parent — the order the builder renders them in.
     *
     * @return array<int, array<string, mixed>>
     */
    private function rowsFromMenu(Menu $menu): array
    {
        $rows = [];

        foreach ($menu->items as $topLevel) {
            $rows[] = [
                'id' => (string) $topLevel->id,
                'label' => $topLevel->label,
                'type' => $topLevel->type,
                'target_id' => $topLevel->target_id,
                'url' => $topLevel->url,
                'depth' => 0,
            ];

            foreach ($topLevel->children as $child) {
                $rows[] = [
                    'id' => (string) $child->id,
                    'label' => $child->label,
                    'type' => $child->type,
                    'target_id' => $child->target_id,
                    'url' => $child->url,
                    'depth' => 1,
                ];
            }
        }

        return $rows;
    }

    /**
     * Builder rows from a rejected save's old input: same shape, with the
     * payload's request-scoped ids preserved so parent references survive.
     *
     * @param  array<int, mixed>  $oldItems
     * @return array<int, array<string, mixed>>
     */
    private function rowsFromOldInput(array $oldItems): array
    {
        return collect(array_values($oldItems))
            ->map(fn (mixed $item): array => [
                'id' => (string) ($item['id'] ?? ''),
                'label' => (string) ($item['label'] ?? ''),
                'type' => (string) ($item['type'] ?? MenuItem::TYPE_CUSTOM),
                'target_id' => isset($item['target_id']) && $item['target_id'] !== '' ? (int) $item['target_id'] : null,
                'url' => isset($item['url']) && $item['url'] !== '' ? (string) $item['url'] : null,
                'depth' => (string) ($item['parent_id'] ?? '') !== '' ? 1 : 0,
            ])
            ->all();
    }
}
