<?php

namespace Sitewyn\Packages\Media\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sitewyn\Packages\Media\Models\MediaFile;
use Sitewyn\Packages\Media\Models\MediaFolder;
use Sitewyn\Packages\Media\Repositories\MediaFileRepository;
use Sitewyn\Packages\Media\Repositories\MediaFolderRepository;
use Sitewyn\Packages\Media\Support\MediaFilePayload;

class MediaPickerController extends Controller
{
    public function __construct(
        private readonly MediaFolderRepository $folders,
        private readonly MediaFileRepository $files,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $folder = $this->currentFolder($request);
        $folderId = $folder?->id;
        $search = trim((string) $request->query('q', ''));

        return response()->json([
            'current_folder' => $folder ? $this->folderPayload($folder) : null,
            'breadcrumbs' => collect($this->breadcrumbs($folder))
                ->map(fn (MediaFolder $folder): array => $this->folderPayload($folder))
                ->values(),
            'folders' => ($search === ''
                ? $this->folders->childrenOf($folderId)
                : $this->folders->searchByName($search, $folderId))
                ->map(fn (MediaFolder $folder): array => $this->folderPayload($folder))
                ->values(),
            'files' => ($search === '' ? $this->files->inFolder($folderId) : $this->files->search($search, $folderId))
                ->map(fn (MediaFile $file): array => MediaFilePayload::make($file))
                ->values(),
            'search' => $search,
        ]);
    }

    private function currentFolder(Request $request): ?MediaFolder
    {
        $folderId = $request->integer('folder');

        if (! $folderId) {
            return null;
        }

        return MediaFolder::query()->findOrFail($folderId);
    }

    /**
     * @return array<int, MediaFolder>
     */
    private function breadcrumbs(?MediaFolder $folder): array
    {
        $breadcrumbs = [];

        while ($folder) {
            array_unshift($breadcrumbs, $folder);
            $folder = $folder->parent;
        }

        return $breadcrumbs;
    }

    /**
     * @return array<string, mixed>
     */
    private function folderPayload(MediaFolder $folder): array
    {
        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'path' => $folder->path,
        ];
    }
}
