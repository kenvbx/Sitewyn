<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Page\Models\Page;

class SearchController extends Controller
{
    private const LIMIT_PER_GROUP = 5;

    /**
     * Static quick links surfaced for every admin (and the only group when
     * the query is empty). Entries are skipped when the current user lacks
     * the permission or the route is not registered (inactive plugin).
     */
    private const QUICK_LINKS = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home', 'permission' => null],
        ['label' => 'Media', 'route' => 'admin.media.index', 'icon' => 'media', 'permission' => 'media.index'],
        ['label' => 'Menus', 'route' => 'admin.menus.index', 'icon' => 'menu', 'permission' => 'menus.manage'],
        ['label' => 'Widgets', 'route' => 'admin.widgets.index', 'icon' => 'widget', 'permission' => 'widgets.manage'],
        ['label' => 'Plugins', 'route' => 'admin.plugins.index', 'icon' => 'plugin', 'permission' => 'plugins.manage'],
        ['label' => 'Settings', 'route' => 'admin.settings.edit', 'icon' => 'settings', 'permission' => 'settings.edit'],
        ['label' => 'Audit Logs', 'route' => 'admin.audit-logs.index', 'icon' => 'audit', 'permission' => 'audit.index'],
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user('admin');
        $query = trim((string) $request->query('q', ''));

        $groups = [];

        if ($query !== '') {
            foreach ([
                $this->pagesGroup($query, $user),
                $this->postsGroup($query, $user),
                $this->usersGroup($query, $user),
            ] as $group) {
                if ($group !== null) {
                    $groups[] = $group;
                }
            }
        }

        $quickLinks = $this->quickLinksGroup($user);

        if ($quickLinks !== null) {
            $groups[] = $quickLinks;
        }

        return response()->json([
            'groups' => $groups,
        ]);
    }

    /**
     * Draft pages are included on purpose: this is an admin search, not the
     * frontend.
     */
    private function pagesGroup(string $query, User $user): ?array
    {
        if (! Route::has('admin.pages.edit') || ! $this->allowed($user, 'page.index')) {
            return null;
        }

        $pages = Page::query()
            ->where('title', 'like', '%'.$query.'%')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(self::LIMIT_PER_GROUP)
            ->get(['id', 'title']);

        if ($pages->isEmpty()) {
            return null;
        }

        return [
            'label' => 'Pages',
            'items' => $pages
                ->map(fn (Page $page): array => $this->item($page->title, route('admin.pages.edit', ['page' => $page->id], false), 'page'))
                ->all(),
        ];
    }

    private function postsGroup(string $query, User $user): ?array
    {
        if (! Route::has('admin.posts.edit') || ! $this->allowed($user, 'post.index')) {
            return null;
        }

        $posts = Post::query()
            ->where('title', 'like', '%'.$query.'%')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(self::LIMIT_PER_GROUP)
            ->get(['id', 'title']);

        if ($posts->isEmpty()) {
            return null;
        }

        return [
            'label' => 'Posts',
            'items' => $posts
                ->map(fn (Post $post): array => $this->item($post->title, route('admin.posts.edit', ['post' => $post->id], false), 'post'))
                ->all(),
        ];
    }

    private function usersGroup(string $query, User $user): ?array
    {
        if (! Route::has('admin.users.edit') || ! $this->allowed($user, 'users.index')) {
            return null;
        }

        // Only id/name/email are selected — credentials never leave the model.
        $users = User::query()
            ->where(fn ($builder) => $builder
                ->where('name', 'like', '%'.$query.'%')
                ->orWhere('email', 'like', '%'.$query.'%'))
            ->orderBy('name')
            ->orderBy('id')
            ->limit(self::LIMIT_PER_GROUP)
            ->get(['id', 'name', 'email']);

        if ($users->isEmpty()) {
            return null;
        }

        return [
            'label' => 'Users',
            'items' => $users
                ->map(fn (User $found): array => $this->item($found->name, route('admin.users.edit', ['user' => $found->id], false), 'users'))
                ->all(),
        ];
    }

    /**
     * @return array{label: string, items: array<int, array{title: string, subtitle: string, url: string, icon: string}>}|null
     */
    private function quickLinksGroup(User $user): ?array
    {
        $items = [];

        foreach (self::QUICK_LINKS as $link) {
            if (! Route::has($link['route']) || ! $this->allowed($user, $link['permission'])) {
                continue;
            }

            $items[] = $this->item($link['label'], route($link['route'], [], false), $link['icon']);
        }

        if ($items === []) {
            return null;
        }

        return [
            'label' => 'Quick links',
            'items' => $items,
        ];
    }

    /**
     * @return array{title: string, subtitle: string, url: string, icon: string}
     */
    private function item(string $title, string $url, string $icon): array
    {
        return [
            'title' => $title,
            'subtitle' => $url,
            'url' => $url,
            'icon' => $icon,
        ];
    }

    private function allowed(User $user, ?string $permission): bool
    {
        return $permission === null || $user->hasAnyPermission([$permission]);
    }
}
