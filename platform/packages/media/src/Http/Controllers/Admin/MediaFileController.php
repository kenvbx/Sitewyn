<?php

namespace Sitewyn\Packages\Media\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Sitewyn\Packages\Media\Http\Requests\Admin\UpdateMediaFileRequest;
use Sitewyn\Packages\Media\Models\MediaFile;
use Sitewyn\Packages\Media\Repositories\MediaFileRepository;

class MediaFileController extends Controller
{
    public function __construct(private readonly MediaFileRepository $files) {}

    public function update(UpdateMediaFileRequest $request, MediaFile $file): RedirectResponse
    {
        $validated = $request->validated();

        if (array_key_exists('name', $validated)) {
            $this->files->rename($file, $validated['name']);
        }

        if (array_key_exists('folder_id', $validated)) {
            $this->files->move($file, $request->integer('folder_id') ?: null);
        }

        admin_flash()->success(__('File updated successfully.'));

        return back();
    }

    public function destroy(MediaFile $file): RedirectResponse
    {
        $this->files->deleteWithFiles($file);

        admin_flash()->success(__('File deleted successfully.'));

        return back();
    }
}
