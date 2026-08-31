<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Models\AuditLog;

class AuditLogController extends Controller
{
    /**
     * Audit logs grow without bound, so the index is always paginated.
     */
    private const PER_PAGE = 50;

    public function index(Request $request): View
    {
        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        // Only accept a filter that is an actually recorded action.
        $action = (string) $request->query('action', '');
        $activeAction = $actions->contains($action) ? $action : null;

        $logs = AuditLog::query()
            ->when($activeAction !== null, fn ($query) => $query->where('action', $activeAction))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $users = User::query()
            ->whereIn('id', $logs->getCollection()->pluck('user_id')->filter()->unique())
            ->pluck('name', 'id');

        return view('core/base::admin.audit-logs.index', [
            'logs' => $logs,
            'users' => $users,
            'actions' => $actions,
            'activeAction' => $activeAction,
        ]);
    }
}
