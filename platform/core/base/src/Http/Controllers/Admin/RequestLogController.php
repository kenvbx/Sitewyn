<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Models\RequestLog;

class RequestLogController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request): View
    {
        return view('core/base::admin.request-logs.index', [
            'groups' => $this->groups($request),
            'search' => (string) $request->query('search', ''),
        ]);
    }

    public function destroy(RequestLog $requestLog): RedirectResponse
    {
        $deleted = RequestLog::query()
            ->where('url', $requestLog->url)
            ->where('status_code', $requestLog->status_code)
            ->delete();

        admin_flash()->success(__('Deleted :count request log records.', ['count' => $deleted]));

        return redirect()->route('admin.request-logs.index');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['array'],
            'ids.*' => ['integer'],
        ]);

        $deleted = $this->deleteGroups($data['ids'] ?? []);

        if ($deleted === 0) {
            admin_flash()->warning(__('Select at least one request log to delete.'));

            return redirect()->route('admin.request-logs.index');
        }

        admin_flash()->success(__('Deleted :count request log records.', ['count' => $deleted]));

        return redirect()->route('admin.request-logs.index');
    }

    public function clear(): RedirectResponse
    {
        $deleted = RequestLog::query()->delete();

        admin_flash()->success(__('Deleted :count request log records.', ['count' => $deleted]));

        return redirect()->route('admin.request-logs.index');
    }

    private function groups(Request $request): LengthAwarePaginator
    {
        $search = trim((string) $request->query('search', ''));

        return RequestLog::query()
            ->selectRaw('MIN(id) as id, url, status_code, COUNT(*) as records_count')
            ->when($search !== '', fn ($query) => $query->where('url', 'like', '%'.$search.'%'))
            ->groupBy('url', 'status_code')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    private function deleteGroups(array $ids): int
    {
        $deleted = 0;

        RequestLog::query()
            ->whereIn('id', $ids)
            ->get(['url', 'status_code'])
            ->each(function (RequestLog $log) use (&$deleted): void {
                $deleted += RequestLog::query()
                    ->where('url', $log->url)
                    ->where('status_code', $log->status_code)
                    ->delete();
            });

        return $deleted;
    }
}
