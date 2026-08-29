<?php

namespace Sitewyn\Packages\Media\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Sitewyn\Packages\Media\Http\Requests\Admin\UpdateMediaFolderRequest;
use Sitewyn\Packages\Media\Models\MediaFolder;
use Sitewyn\Packages\Media\Repositories\MediaFileRepository;
use Sitewyn\Packages\Media\Repositories\MediaFolderRepository;

class MediaFolderActionController extends Controller
{
    public function __construct(
        private readonly MediaFolderRepository $folders,
        private readonly MediaFileRepository $files,
    ) {}

    public function update(UpdateMediaFolderRequest $request, MediaFolder $folder): RedirectResponse
    {
        $validated = $request->validated();

        if (array_key_exists('parent_id', $validated)) {
            $parentId = $request->integer('parent_id') ?: null;

            if ($parentId === $folder->id) {
                throw ValidationException::withMessages([
                    'parent_id' => __('A folder cannot be moved into itself.'),
                ]);
            }

            if ($parentId) {
                $parent = MediaFolder::query()->findOrFail($parentId);

                if ($this->folders->isDescendantOf($parent, $folder)) {
                    throw ValidationException::withMessages([
                        'parent_id' => __('A folder cannot be moved into one of its child folders.'),
                    ]);
                }
            }

            $this->folders->move($folder, $parentId);
        }

        if (array_key_exists('name', $validated)) {
            $this->folders->rename($folder, $validated['name']);
        }

        admin_flash()->success(__('Folder updated successfully.'));

        return back();
    }

    public function destroy(MediaFolder $folder): RedirectResponse
    {
        $this->folders->deleteTree($folder, $this->files);

        admin_flash()->success(__('Folder deleted successfully.'));

        return redirect()->route('admin.media.index');
    }
}
