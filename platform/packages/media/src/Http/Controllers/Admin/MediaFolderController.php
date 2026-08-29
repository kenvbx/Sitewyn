<?php

namespace Sitewyn\Packages\Media\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Sitewyn\Packages\Media\Http\Requests\Admin\StoreMediaFolderRequest;
use Sitewyn\Packages\Media\Repositories\MediaFolderRepository;

class MediaFolderController extends Controller
{
    public function __construct(private readonly MediaFolderRepository $folders) {}

    public function __invoke(StoreMediaFolderRequest $request): RedirectResponse
    {
        $folder = $this->folders->create($request->validated());

        admin_flash()->success(__('Folder created successfully.'));

        return redirect()->route('admin.media.index', [
            'folder' => $folder->id,
        ]);
    }
}
