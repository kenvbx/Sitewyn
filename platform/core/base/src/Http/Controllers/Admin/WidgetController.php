<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Sitewyn\Core\Base\Http\Requests\Admin\StoreWidgetRequest;
use Sitewyn\Core\Base\Http\Requests\Admin\UpdateWidgetRequest;
use Sitewyn\Core\Base\Models\Widget;
use Sitewyn\Core\Base\Support\ThemeManager;

/**
 * Widget areas (P5-04). One permission (widgets.manage) gates the whole
 * CRUD plus reordering — arranging an area is a single editing activity,
 * like the menus builder. The areas themselves live in the active theme's
 * theme.json; this controller only validates slugs against them.
 */
class WidgetController extends Controller
{
    public function index(Request $request): View
    {
        $areas = app(ThemeManager::class)->widgetAreas();
        $areaSlug = $this->selectedArea($request, $areas);

        return view('core/base::admin.widgets.index', [
            'areas' => $areas,
            'areaSlug' => $areaSlug,
            'widgets' => $areaSlug === null
                ? collect()
                : Widget::query()->inArea($areaSlug)->get(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $areas = app(ThemeManager::class)->widgetAreas();
        $areaSlug = $this->selectedArea($request, $areas);

        if ($areaSlug === null) {
            // Nothing to attach a widget to — back to the area picker's
            // empty state.
            return redirect()->route('admin.widgets.index');
        }

        return view('core/base::admin.widgets.create', [
            'widget' => new Widget,
            'areas' => $areas,
            'areaSlug' => $areaSlug,
            'types' => $this->typeOptions(),
        ]);
    }

    public function store(StoreWidgetRequest $request): RedirectResponse
    {
        $attributes = $request->validated();

        $widget = Widget::query()->create([
            'area_slug' => $attributes['area_slug'],
            'type' => $attributes['type'],
            'data' => $this->dataFor($attributes['type'], $attributes['data'] ?? []),
            // New widgets join the bottom of their area.
            'order' => (int) Widget::query()->where('area_slug', $attributes['area_slug'])->max('order') + 1,
        ]);

        admin_flash()->success(__('Widget created successfully.'));

        return redirect()->route('admin.widgets.index', ['area' => $widget->area_slug]);
    }

    public function edit(Widget $widget): View
    {
        return view('core/base::admin.widgets.edit', [
            'widget' => $widget,
            'areas' => app(ThemeManager::class)->widgetAreas(),
            'areaSlug' => $widget->area_slug,
            'types' => $this->typeOptions(),
        ]);
    }

    public function update(UpdateWidgetRequest $request, Widget $widget): RedirectResponse
    {
        $attributes = $request->validated();

        $widget->update([
            'type' => $attributes['type'],
            'data' => $this->dataFor($attributes['type'], $attributes['data'] ?? []),
        ]);

        admin_flash()->success(__('Widget updated successfully.'));

        return redirect()->route('admin.widgets.index', ['area' => $widget->area_slug]);
    }

    /**
     * Reorder by swapping the order value with the direct neighbour in the
     * area — the ↑/↓ buttons move a widget exactly one row per click.
     */
    public function move(Request $request, Widget $widget): RedirectResponse
    {
        $validated = $request->validate([
            'direction' => ['required', 'string', 'in:up,down'],
        ]);

        $widgets = Widget::query()->inArea($widget->area_slug)->get();
        $index = $widgets->search(fn (Widget $row): bool => $row->id === $widget->id);
        $neighborIndex = $validated['direction'] === 'up' ? $index - 1 : $index + 1;

        if ($index !== false && isset($widgets[$neighborIndex])) {
            $neighbor = $widgets[$neighborIndex];

            DB::transaction(function () use ($widget, $neighbor): void {
                $order = $widget->order;
                $widget->update(['order' => $neighbor->order]);
                $neighbor->update(['order' => $order]);
            });
        }

        admin_flash()->success(__('Widget order updated.'));

        return redirect()->route('admin.widgets.index', ['area' => $widget->area_slug]);
    }

    public function destroy(Widget $widget): RedirectResponse
    {
        $areaSlug = $widget->area_slug;
        $widget->delete();

        admin_flash()->success(__('Widget deleted successfully.'));

        return redirect()->route('admin.widgets.index', ['area' => $areaSlug]);
    }

    /**
     * The area the admin is working in: ?area= when it points at a declared
     * area, otherwise the first declared one — never an area the theme does
     * not know about. Null when the theme declares no areas at all.
     *
     * @param  array<int, array{slug: string, name: string}>  $areas
     */
    private function selectedArea(Request $request, array $areas): ?string
    {
        if ($areas === []) {
            return null;
        }

        $requested = (string) $request->query('area', '');

        foreach ($areas as $area) {
            if ($requested === $area['slug']) {
                return $area['slug'];
            }
        }

        return $areas[0]['slug'];
    }

    /**
     * @return array<string, string> type => label for the admin select
     */
    private function typeOptions(): array
    {
        return [
            Widget::TYPE_PAGES => 'Pages list',
            Widget::TYPE_RECENT_POSTS => 'Recent posts',
            Widget::TYPE_TEXT => 'Text',
        ];
    }

    /**
     * Keep only the payload keys the widget type actually uses — the admin
     * posts the full field set for every type.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function dataFor(string $type, array $data): array
    {
        $payload = [];

        $title = trim((string) ($data['title'] ?? ''));

        if ($title !== '') {
            $payload['title'] = $title;
        }

        if ($type === Widget::TYPE_RECENT_POSTS && isset($data['limit'])) {
            $payload['limit'] = (int) $data['limit'];
        }

        if ($type === Widget::TYPE_TEXT && isset($data['content'])) {
            $payload['content'] = (string) $data['content'];
        }

        return $payload;
    }
}
