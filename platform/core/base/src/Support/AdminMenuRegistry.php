<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Support\Collection;
use InvalidArgumentException;

class AdminMenuRegistry
{
    /**
     * @var array<string, array{id: string, title: string, route: string|null, url: string|null, icon: string|null, permission: string|array<int, string>|null, active: array<int, string>, order: int, children: array<int, array<string, mixed>>}>
     */
    private array $items = [];

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function register(array $items): void
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function add(array $item): void
    {
        $normalized = $this->normalize($item);
        $this->items[$normalized['id']] = $normalized;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function all(): Collection
    {
        return collect($this->items)
            ->sortBy('order')
            ->values()
            ->map(fn (array $item): array => [
                ...$item,
                'children' => collect($item['children'])->sortBy('order')->values()->all(),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function visibleFor(?object $user): Collection
    {
        return $this->all()
            ->map(fn (array $item): ?array => $this->visibleItem($item, $user))
            ->filter()
            ->values();
    }

    public function has(string $id): bool
    {
        return isset($this->items[$id]);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{id: string, title: string, route: string|null, url: string|null, icon: string|null, permission: string|array<int, string>|null, active: array<int, string>, order: int, children: array<int, array<string, mixed>>}
     */
    private function normalize(array $item): array
    {
        $id = (string) ($item['id'] ?? '');
        $title = (string) ($item['title'] ?? '');

        if ($id === '' || $title === '') {
            throw new InvalidArgumentException('Admin menu id and title are required.');
        }

        return [
            'id' => $id,
            'title' => $title,
            'route' => isset($item['route']) ? (string) $item['route'] : null,
            'url' => isset($item['url']) ? (string) $item['url'] : null,
            'icon' => isset($item['icon']) ? (string) $item['icon'] : null,
            'permission' => $item['permission'] ?? null,
            'active' => collect($item['active'] ?? [isset($item['route']) ? (string) $item['route'] : null])
                ->filter()
                ->values()
                ->all(),
            'order' => (int) ($item['order'] ?? 0),
            'children' => collect($item['children'] ?? [])
                ->map(fn (array $child): array => $this->normalize($child))
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private function visibleItem(array $item, ?object $user): ?array
    {
        $children = collect($item['children'])
            ->map(fn (array $child): ?array => $this->visibleItem($child, $user))
            ->filter()
            ->values()
            ->all();

        if ($item['children'] !== [] && $children === [] && $item['route'] === null && $item['url'] === null) {
            return null;
        }

        if (! $this->allowed($item['permission'], $user) && $children === []) {
            return null;
        }

        return [
            ...$item,
            'children' => $children,
            'active' => $this->active($item, $children),
        ];
    }

    /**
     * @param  string|array<int, string>|null  $permission
     */
    private function allowed(string|array|null $permission, ?object $user): bool
    {
        if ($permission === null || $permission === []) {
            return true;
        }

        if (! $user || ! method_exists($user, 'hasAnyPermission')) {
            return false;
        }

        return $user->hasAnyPermission((array) $permission);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, array<string, mixed>>  $children
     */
    private function active(array $item, array $children): bool
    {
        if (collect($item['active'])->contains(fn (string $pattern): bool => request()->routeIs($pattern))) {
            return true;
        }

        return collect($children)->contains(fn (array $child): bool => (bool) $child['active']);
    }
}
