<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Support\Collection;
use Sitewyn\Core\Base\Models\Widget;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Blog\Repositories\PostRepository;
use Sitewyn\Packages\Page\Models\Page;
use Sitewyn\Packages\Page\Repositories\PageRepository;

/**
 * Resolves stored widgets (P5-04) into renderable payloads. Core owns the
 * data logic — which pages/posts a widget lists — while the active theme
 * owns the presentation through its widgets/{type} partials. Reading the
 * page/blog repositories here is the same accepted coupling the menu
 * builder already has: widgets list content, content cannot exist without it.
 */
class WidgetRenderer
{
    /**
     * Widgets of one area in save order, each resolved to a payload for the
     * theme partials: ['widget' => Widget, 'title' => ?string, 'resolved' => mixed].
     * Types the renderer cannot resolve (rows with a stale or unknown type)
     * are skipped silently instead of crashing the frontend.
     *
     * @return Collection<int, array{widget: Widget, title: ?string, resolved: mixed}>
     */
    public function resolveWidgets(string $areaSlug): Collection
    {
        return Widget::query()
            ->inArea($areaSlug)
            ->get()
            ->map(fn (Widget $widget): ?array => $this->resolve($widget))
            ->filter()
            ->values();
    }

    /**
     * Widget types core can resolve into payloads — the types the admin
     * form offers and the DB may hold.
     *
     * @return array<int, string>
     */
    public function renderableTypes(): array
    {
        return Widget::TYPES;
    }

    /**
     * @return array{widget: Widget, title: ?string, resolved: mixed}|null
     */
    private function resolve(Widget $widget): ?array
    {
        $data = $widget->data ?? [];

        return match ($widget->type) {
            Widget::TYPE_PAGES => [
                'widget' => $widget,
                'title' => $this->title($data),
                // Published pages, title-ordered — the same shape the theme
                // nav fallback uses.
                'resolved' => app(PageRepository::class)->byStatus(Page::STATUS_PUBLISHED),
            ],
            Widget::TYPE_RECENT_POSTS => [
                'widget' => $widget,
                'title' => $this->title($data),
                // Newest published posts first, capped at the configured limit.
                'resolved' => app(PostRepository::class)
                    ->byStatus(Post::STATUS_PUBLISHED)
                    ->take((int) ($data['limit'] ?? 5))
                    ->values(),
            ],
            Widget::TYPE_TEXT => [
                'widget' => $widget,
                'title' => $this->title($data),
                // Admin-authored rich text — rendered raw by the theme with
                // {!! !!} under the same trust model as page content.
                'resolved' => (string) ($data['content'] ?? ''),
            ],
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function title(array $data): ?string
    {
        $title = $data['title'] ?? null;

        return is_string($title) && trim($title) !== '' ? trim($title) : null;
    }
}
