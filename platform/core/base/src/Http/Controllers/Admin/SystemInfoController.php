<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Support\SystemInformationService;

class SystemInfoController extends Controller
{
    public function __construct(private readonly SystemInformationService $systemInformation) {}

    public function __invoke(Request $request): View
    {
        return view('core/base::admin.system-info.index', [
            'packages' => $this->systemInformation->packages(),
            'systemEnvironment' => $this->systemInformation->systemEnvironment($request),
            'serverEnvironment' => $this->systemInformation->serverEnvironment($request),
            'databaseInformation' => $this->systemInformation->databaseInformation(),
            'phpConfiguration' => $this->systemInformation->phpConfiguration(),
            'report' => $this->systemInformation->report($request),
        ]);
    }
}
