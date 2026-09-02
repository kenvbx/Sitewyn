<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Support\SystemUpdaterService;

class SystemUpdaterController extends Controller
{
    public function __construct(private readonly SystemUpdaterService $updater) {}

    public function index(Request $request): View
    {
        return view('core/base::admin.updater.index', [
            'status' => $this->updater->status(),
            'steps' => $this->updater->steps(),
            'completedSteps' => $request->session()->get('system_updater.completed_steps', []),
        ]);
    }

    public function reinstall(Request $request): RedirectResponse
    {
        if (! $this->updater->status()['enabled']) {
            return redirect()->route('admin.system.updater.index')->with('error', 'System updater is disabled.');
        }

        $completed = collect($this->updater->steps())
            ->mapWithKeys(fn (array $step): array => [
                $step['number'] => $this->updater->runStep($step['number']),
            ])
            ->all();

        $request->session()->put('system_updater.completed_steps', $completed);

        return redirect()->route('admin.system.updater.index')->with('status', 'The latest version has been re-installed successfully.');
    }

    public function runStep(Request $request, int $step): RedirectResponse
    {
        if (! $this->updater->status()['enabled']) {
            return redirect()->route('admin.system.updater.index')->with('error', 'System updater is disabled.');
        }

        $completed = $request->session()->get('system_updater.completed_steps', []);
        $result = $this->updater->runStep($step);
        $completed[$step] = $result;

        $request->session()->put('system_updater.completed_steps', $completed);

        return redirect()->route('admin.system.updater.index')->with('status', $result['title'].' completed.');
    }
}
