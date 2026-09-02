<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Support\AdminFlash;
use Sitewyn\Core\Base\Support\CacheManagementService;
use Throwable;

class CacheController extends Controller
{
    public function __construct(
        private readonly CacheManagementService $cache,
        private readonly AdminFlash $flash,
    ) {}

    public function index(): View
    {
        return view('core/base::admin.cache.index', [
            'cacheRows' => $this->cache->cacheRows(),
            'optimizationRows' => $this->cache->optimizationRows(),
        ]);
    }

    public function run(string $operation): RedirectResponse
    {
        try {
            $this->cache->run($operation);
            $this->flash->success($this->cache->operationTitle($operation).' completed.');
        } catch (Throwable $exception) {
            report($exception);
            $this->flash->error('Unable to run '.$this->cache->operationTitle($operation).'.');
        }

        return redirect()->route('admin.system.cache.index');
    }
}
