<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Sitewyn\Core\Base\Support\BackupService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups) {}

    public function index(): View
    {
        return view('core/base::admin.backups.index', [
            'backups' => $this->backups->list(),
        ]);
    }

    public function create(): RedirectResponse
    {
        try {
            $name = $this->backups->create();
        } catch (Throwable $exception) {
            admin_flash()->error($exception->getMessage());

            return redirect()->route('admin.system.backups.index');
        }

        admin_flash()->success("Backup [{$name}] created.");

        return redirect()->route('admin.system.backups.index');
    }

    public function download(string $name): BinaryFileResponse
    {
        try {
            $path = $this->backups->download($name);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        return response()->download($path, $name);
    }

    public function downloadDatabase(string $name): Response
    {
        try {
            $contents = $this->backups->databaseDump($name);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        return response($contents, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="'.$this->backups->databaseDownloadName($name).'"',
        ]);
    }

    public function downloadUploads(string $name): BinaryFileResponse
    {
        try {
            $archive = $this->backups->uploadsArchive($name);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        return response()->download($archive['path'], $archive['name']);
    }

    public function restore(string $name): RedirectResponse
    {
        try {
            $this->backups->restore($name);
        } catch (InvalidArgumentException) {
            abort(404);
        } catch (Throwable $exception) {
            admin_flash()->error($exception->getMessage());

            return redirect()->route('admin.system.backups.index');
        }

        admin_flash()->success(
            "Backup [{$name}] restored. All current data and media were replaced with the snapshot."
        );

        return redirect()->route('admin.system.backups.index');
    }

    public function delete(string $name): RedirectResponse
    {
        try {
            $this->backups->delete($name);
        } catch (InvalidArgumentException) {
            abort(404);
        } catch (Throwable $exception) {
            admin_flash()->error($exception->getMessage());

            return redirect()->route('admin.system.backups.index');
        }

        admin_flash()->success("Backup [{$name}] deleted.");

        return redirect()->route('admin.system.backups.index');
    }
}
