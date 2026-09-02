<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Support\AdminFlash;
use Sitewyn\Core\Base\Support\CleanupService;

class CleanupController extends Controller
{
    public function __construct(
        private readonly CleanupService $cleanup,
        private readonly AdminFlash $flash,
    ) {}

    public function index(): View
    {
        return view('core/base::admin.cleanup.index', [
            'enabled' => $this->cleanup->enabled(),
            'tables' => $this->cleanup->tables(),
        ]);
    }

    public function cleanup(Request $request): RedirectResponse
    {
        if (! $this->cleanup->enabled()) {
            $this->flash->warning('Cleanup System is not enabled yet.');

            return redirect()->route('admin.system.cleanup.index');
        }

        $ignoredTables = collect($request->input('ignored_tables', []))
            ->filter(fn (mixed $table): bool => is_string($table) && $table !== '')
            ->values()
            ->all();

        $result = $this->cleanup->cleanup($ignoredTables);

        $this->flash->success("Cleanup completed. {$result['cleaned']} tables cleaned, {$result['skipped']} tables ignored.");

        return redirect()->route('admin.system.cleanup.index');
    }
}
