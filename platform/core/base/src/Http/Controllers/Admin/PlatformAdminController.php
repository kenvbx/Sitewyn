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
        ['title' => 'Roles & Permissions', 'description' => 'View and update your roles and permissions.', 'icon' => 'roles', 'url' => '/admin/system/roles', 'permission' => 'roles.index'],
        ['title' => 'Request Logs', 'description' => 'View and delete your system request logs.', 'icon' => 'request-log', 'url' => '/admin/request-logs', 'permission' => 'request_logs.index'],
        ['title' => 'Activity Logs', 'description' => 'View and delete your system activity logs.', 'icon' => 'audit', 'url' => '/admin/audit-logs', 'permission' => 'audit.index'],
        ['title' => 'Backups', 'description' => 'Backup database and uploads folder.', 'icon' => 'backup', 'url' => '/admin/system/backups', 'permission' => 'backups.manage'],
        ['title' => 'Cronjob', 'description' => 'Set up automated background tasks to keep your website running smoothly.', 'icon' => 'clock', 'url' => '/admin/system/cronjob', 'permission' => 'cronjobs.manage'],
        ['title' => 'Security Settings', 'description' => 'Manage cookie security and HTTP headers', 'icon' => 'shield', 'url' => '/admin/system/security', 'permission' => 'security.manage'],
        ['title' => 'Cache Management', 'description' => 'Clear cache to make your site up to date.', 'icon' => 'reload', 'url' => '/admin/system/cache', 'permission' => 'settings.cache'],
        ['title' => 'Cleanup System', 'description' => 'Cleanup your unused data in database.', 'icon' => 'trash', 'url' => '/admin/system/cleanup', 'permission' => 'cleanup.manage'],
        ['title' => 'System Information', 'description' => 'All information about current system configuration.', 'icon' => 'info-circle', 'url' => '/admin/system/info', 'permission' => 'system.info'],
        ['title' => 'System Updater', 'description' => 'Update your system to the latest version.', 'icon' => 'rocket', 'url' => '/admin/system/updater', 'permission' => 'system.updater'],
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
