<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PlatformAdminController extends Controller
{
    /**
     * Administration tool cards on the hub, in display order. Like the
     * Dashboard route, the hub itself is open to every signed-in admin —
     * each card hides itself when the user lacks its permission. The Users
     * card is the exception: it is gated on team membership (User::isTeamMember())
     * instead of a permission.
     */
    private const CARDS = [
        ['title' => 'Users', 'description' => 'View and update your system users.', 'icon' => 'users', 'url' => '/admin/system/users', 'team' => true],
        ['title' => 'Roles & Permissions', 'description' => 'View and update your roles and permissions.', 'icon' => 'roles', 'url' => '/admin/roles', 'permission' => 'roles.index'],
        ['title' => 'Permissions', 'description' => 'Browse every registered permission by module.', 'icon' => 'key', 'url' => '/admin/permissions', 'permission' => 'permissions.index'],
        ['title' => 'Media', 'description' => 'Manage your media library files and folders.', 'icon' => 'media', 'url' => '/admin/media', 'permission' => 'media.index'],
        ['title' => 'Menus', 'description' => 'Build and organize your frontend navigation.', 'icon' => 'menu', 'url' => '/admin/menus', 'permission' => 'menus.manage'],
        ['title' => 'Widgets', 'description' => 'Place content widgets into your theme areas.', 'icon' => 'widget', 'url' => '/admin/widgets', 'permission' => 'widgets.manage'],
        ['title' => 'Plugins', 'description' => 'Activate or deactivate platform plugins.', 'icon' => 'plugin', 'url' => '/admin/plugins', 'permission' => 'plugins.manage'],
        ['title' => 'Audit Logs', 'description' => 'Review every recorded admin activity.', 'icon' => 'audit', 'url' => '/admin/audit-logs', 'permission' => 'audit.index'],
        ['title' => 'Backups', 'description' => 'Back up your database and uploads folder.', 'icon' => 'backup', 'url' => '/admin/backups', 'permission' => 'backups.manage'],
        ['title' => 'Settings', 'description' => 'Configure your site name, theme and options.', 'icon' => 'settings', 'url' => '/admin/settings', 'permission' => 'settings.edit'],
    ];

    public function __invoke(Request $request): View
    {
        $user = $request->user('admin');

        $cards = collect(self::CARDS)
            ->filter(fn (array $card): bool => $this->allowed($user, $card))
            ->values();

        return view('core/base::admin.platform.index', [
            'cards' => $cards,
        ]);
    }

    /**
     * Team-gated cards require team membership (User::isTeamMember(): super
     * admins and anyone holding the built-in Admin role); every other card
     * follows the same visibility rule as AdminMenuRegistry::allowed(): no
     * permission means every admin sees the entry, otherwise the user needs it.
     *
     * @param  array<string, mixed>  $card
     */
    private function allowed(?object $user, array $card): bool
    {
        if (($card['team'] ?? false) === true) {
            return $user instanceof User && $user->exists && $user->isTeamMember();
        }

        $permission = $card['permission'] ?? null;

        if ($permission === null) {
            return true;
        }

        return $user !== null && $user->hasAnyPermission([$permission]);
    }
}
